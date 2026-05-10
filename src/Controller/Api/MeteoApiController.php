<?php

namespace App\Controller\Api;

use App\Repository\PlanningRepository;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/meteo')]
class MeteoApiController extends AbstractController
{
    private string $apiKey;
    private Client $client;

    public function __construct()
    {
        $this->apiKey = 'fcad433818e55f921993e9dd0d67f6a1';
        $this->client = new Client();
    }

    // ==================== HELPERS TYPE-SAFE ====================

    private function mixedToFloat(mixed $value, float $default = 0.0): float
    {
        if (is_float($value)) return $value;
        if (is_int($value)) return (float) $value;
        if (is_string($value) && is_numeric($value)) return (float) $value;
        return $default;
    }

    private function mixedToInt(mixed $value, int $default = 0): int
    {
        if (is_int($value)) return $value;
        if (is_float($value)) return (int) $value;
        if (is_string($value) && is_numeric($value)) return (int) $value;
        return $default;
    }

    private function mixedToString(mixed $value, string $default = ''): string
    {
        if (is_string($value)) return $value;
        if (is_int($value) || is_float($value)) return (string) $value;
        return $default;
    }

    /**
     * Safely extract the first element of a mixed array field.
     * Returns the element only if $list is a non-empty array, otherwise null.
     *
     * @param mixed $list
     * @return mixed
     */
    private function firstItem(mixed $list): mixed
    {
        if (is_array($list) && isset($list[0])) {
            return $list[0];
        }
        return null;
    }

    // ==================== 1. MÉTÉO POUR UN PLANNING ====================
    #[Route('/planning/{id}', name: 'api_meteo_planning', methods: ['GET'])]
    public function meteoForPlanning(int $id, PlanningRepository $planningRepository, Request $request): JsonResponse
    {
        $planning = $planningRepository->find($id);
        if (!$planning) {
            return $this->json(['error' => 'Planning non trouvé'], 404);
        }

        $date      = $planning->getDate();
        $typeShift = $planning->getTypeShift();
        $city      = $this->mixedToString($request->query->get('city', 'Tunis,tn'), 'Tunis,tn');

        if ($date instanceof \DateTimeImmutable) {
            $dateTime = \DateTime::createFromImmutable($date);
        } elseif ($date instanceof \DateTime) {
            $dateTime = $date;
        } else {
            $dateTime = new \DateTime();
        }

        $meteo  = $this->getWeatherForDate($dateTime, $city);
        $advice = $this->getAdviceForShift($meteo, $this->mixedToString($typeShift, 'JOUR'));

        return $this->json([
            'success' => true,
            'data' => [
                'planning_id'    => $id,
                'date'           => $dateTime->format('d/m/Y'),
                'shift'          => $typeShift,
                'meteo'          => $meteo,
                'conseils'       => $advice,
                'recommandation' => $this->getWorkRecommendation($meteo),
            ]
        ]);
    }

    // ==================== 2. PRÉVISIONS POUR LA SEMAINE ====================
    #[Route('/week', name: 'api_meteo_week', methods: ['GET'])]
    public function weekForecast(Request $request): JsonResponse
    {
        $city = $this->mixedToString($request->query->get('city', 'Tunis,tn'), 'Tunis,tn');

        return $this->json([
            'success'  => true,
            'city'     => $city,
            'forecast' => $this->getWeeklyForecast($city),
        ]);
    }

    // ==================== 3. MÉTÉO ACTUELLE ====================
    #[Route('/now', name: 'api_meteo_now', methods: ['GET'])]
    public function currentWeather(Request $request): JsonResponse
    {
        $city = $this->mixedToString($request->query->get('city', 'Tunis,tn'), 'Tunis,tn');

        return $this->json([
            'success' => true,
            'city'    => $city,
            'weather' => $this->getCurrentWeather($city),
        ]);
    }

    // ==================== APPELS API OPENWEATHERMAP ====================

    /**
     * @return array<string, mixed>
     */
    private function getWeatherForDate(\DateTime $date, string $city): array
    {
        try {
            return $this->getCurrentWeather($city);
        } catch (\Exception $e) {
            return $this->getFallbackWeather();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getCurrentWeather(string $city): array
    {
        try {
            $url      = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$this->apiKey}&units=metric&lang=fr";
            $response = $this->client->get($url);
            $data     = json_decode((string) $response->getBody(), true);

            if (!is_array($data)) {
                return $this->getFallbackWeather();
            }

            $main = is_array($data['main'] ?? null) ? $data['main'] : [];

            // FIX lines 141/146/149: $data['weather'] is mixed — use firstItem() helper to safely
            // get index 0, then guard with is_array before accessing 'description' / 'icon' offsets.
            $weatherRaw  = $data['weather'] ?? null;
            $weatherItem = $this->firstItem($weatherRaw);
            $weather     = is_array($weatherItem) ? $weatherItem : [];

            $wind = is_array($data['wind'] ?? null) ? $data['wind'] : [];

            return [
                'temperature' => (int) round($this->mixedToFloat($main['temp']        ?? null, 20.0)),
                'condition'   => $this->translateCondition($this->mixedToString($weather['description'] ?? null)),
                'humidity'    => $this->mixedToInt($main['humidity']                   ?? null, 50),
                'wind_speed'  => (int) round($this->mixedToFloat($wind['speed']        ?? null, 0.0)),
                'icon'        => $this->getIconFromCode($this->mixedToString($weather['icon'] ?? null, '01d')),
                'temp_min'    => (int) round($this->mixedToFloat($main['temp_min']     ?? null, 15.0)),
                'temp_max'    => (int) round($this->mixedToFloat($main['temp_max']     ?? null, 25.0)),
            ];
        } catch (\Exception $e) {
            return $this->getFallbackWeather();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getWeeklyForecast(string $city): array
    {
        try {
            $url      = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$this->apiKey}&units=metric&lang=fr&cnt=7";
            $response = $this->client->get($url);
            $data     = json_decode((string) $response->getBody(), true);

            if (!is_array($data) || !is_array($data['list'] ?? null)) {
                return $this->getFallbackWeeklyForecast();
            }

            $forecast = [];
            $days     = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

            foreach ($data['list'] as $index => $item) {
                if ($index >= 7) break;
                if (!is_array($item)) continue;

                $dtTxt = $this->mixedToString($item['dt_txt'] ?? null, 'now');
                $date  = new \DateTime($dtTxt);
                $main  = is_array($item['main'] ?? null) ? $item['main'] : [];

                // FIX lines 182/190: same pattern — $item['weather'] is mixed, guard before [0] access
                $weatherRaw  = $item['weather'] ?? null;
                $weatherItem = $this->firstItem($weatherRaw);
                $weather     = is_array($weatherItem) ? $weatherItem : [];

                $tempMax = $this->mixedToFloat($main['temp_max'] ?? null, 20.0);

                $forecast[] = [
                    'day'       => $days[(int) $date->format('N') - 1],
                    'date'      => $date->format('d/m'),
                    'temp_min'  => (int) round($this->mixedToFloat($main['temp_min'] ?? null, 15.0)),
                    'temp_max'  => (int) round($tempMax),
                    'condition' => $this->getIconFromCode($this->mixedToString($weather['icon'] ?? null, '01d')),
                    'advice'    => $this->getDailyAdvice((int) round($tempMax)),
                ];
            }

            return $forecast;
        } catch (\Exception $e) {
            return $this->getFallbackWeeklyForecast();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getFallbackWeather(): array
    {
        return [
            'temperature' => rand(18, 28),
            'condition'   => '☀️ Ensoleillé',
            'humidity'    => rand(40, 70),
            'wind_speed'  => rand(5, 15),
            'icon'        => '☀️',
            'temp_min'    => rand(12, 18),
            'temp_max'    => rand(25, 32),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFallbackWeeklyForecast(): array
    {
        $days     = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $icons    = ['☀️', '⛅', '🌧️', '🌬️'];
        $forecast = [];

        for ($i = 0; $i < 7; $i++) {
            $date    = (new \DateTime())->modify("+$i days");
            $tempMax = rand(22, 30);
            $forecast[] = [
                'day'       => $days[$i],
                'date'      => $date->format('d/m'),
                'temp_min'  => rand(12, 18),
                'temp_max'  => $tempMax,
                'condition' => $icons[rand(0, 3)],
                'advice'    => $this->getDailyAdvice($tempMax),
            ];
        }

        return $forecast;
    }

    private function translateCondition(string $description): string
    {
        $conditions = [
            'ciel dégagé'   => '☀️ Ciel dégagé',
            'peu nuageux'   => '⛅ Peu nuageux',
            'nuageux'       => '☁️ Nuageux',
            'couvert'       => '☁️ Couvert',
            'pluie légère'  => '🌧️ Pluie légère',
            'pluie modérée' => '🌧️ Pluie',
            'pluie forte'   => '🌧️ Forte pluie',
            'orage'         => '⛈️ Orage',
            'neige'         => '❄️ Neige',
            'brume'         => '🌫️ Brume',
        ];

        foreach ($conditions as $key => $value) {
            if (strpos($description, $key) !== false) {
                return $value;
            }
        }

        return '🌡️ ' . $description;
    }

    private function getIconFromCode(string $iconCode): string
    {
        $icons = [
            '01d' => '☀️', '01n' => '🌙',
            '02d' => '⛅', '02n' => '☁️',
            '03d' => '☁️', '03n' => '☁️',
            '04d' => '☁️', '04n' => '☁️',
            '09d' => '🌧️', '09n' => '🌧️',
            '10d' => '🌧️', '10n' => '🌧️',
            '11d' => '⛈️', '11n' => '⛈️',
            '13d' => '❄️', '13n' => '❄️',
            '50d' => '🌫️', '50n' => '🌫️',
        ];

        return $icons[$iconCode] ?? '🌡️';
    }

    /**
     * @param array<string, mixed> $meteo
     * @return array<int, string>
     */
    private function getAdviceForShift(array $meteo, string $shift): array
    {
        $temp      = $this->mixedToInt($meteo['temperature'] ?? null, 20);
        $condition = $this->mixedToString($meteo['condition'] ?? null);
        $advice    = [];

        if ($temp > 30) {
            $advice[] = '🌡️ Température élevée : prévoir de l\'eau, pause ombragée';
            $advice[] = '👕 Tenue légère, casquette recommandée';
        } elseif ($temp < 10) {
            $advice[] = '❄️ Température basse : se couvrir chaudement';
            $advice[] = '🧥 Veste imperméable recommandée';
        }

        if (strpos($condition, 'Pluie') !== false) {
            $advice[] = '☔ Risque de pluie : prévoir un imperméable';
        }
        if (strpos($condition, 'Vent') !== false) {
            $advice[] = '💨 Vent : attention aux déplacements';
        }

        switch ($shift) {
            case 'JOUR':  $advice[] = '☀️ Shift de jour : protection solaire recommandée'; break;
            case 'SOIR':  $advice[] = '🌆 Shift de soir : lampe frontale recommandée';     break;
            case 'NUIT':  $advice[] = '🌙 Shift de nuit : se couvrir, températures plus fraîches'; break;
            case 'CONGE': $advice[] = '🏖️ Bonne journée de congé !'; break;
        }

        return $advice;
    }

    /**
     * @param array<string, mixed> $meteo
     */
    private function getWorkRecommendation(array $meteo): string
    {
        $temp      = $this->mixedToInt($meteo['temperature'] ?? null, 20);
        $condition = $this->mixedToString($meteo['condition'] ?? null);

        if ($temp > 35) return '⚠️ ALERTE : Canicule - Reporter les tâches extérieures';
        if ($temp < 0)  return '⚠️ ALERTE : Gel - Prudence sur la route';
        if (strpos($condition, 'Pluie') !== false) return '🌧️ Pluie - Privilégier le travail en intérieur';
        if (strpos($condition, 'Orage') !== false) return '⚠️ Orage - Éviter les déplacements';

        return '✅ Conditions normales - Travail possible';
    }

    private function getDailyAdvice(int $temp): string
    {
        if ($temp > 30) return '🌞 Très chaud, restez hydraté';
        if ($temp > 25) return '☀️ Agréable, pensez à la crème solaire';
        if ($temp > 15) return '😊 Température idéale';
        return '🍂 Un peu frais, couvrez-vous';
    }
}