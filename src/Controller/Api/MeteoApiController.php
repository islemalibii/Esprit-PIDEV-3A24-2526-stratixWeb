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
    private $apiKey;
    private $client;

    public function __construct()
    {
        // TA VRAIE CLÉ API
        $this->apiKey = 'fcad433818e55f921993e9dd0d67f6a1';
        $this->client = new Client();
    }

    // ==================== 1. MÉTÉO POUR UN PLANNING ====================
    #[Route('/planning/{id}', name: 'api_meteo_planning', methods: ['GET'])]
    public function meteoForPlanning(int $id, PlanningRepository $planningRepository, Request $request): JsonResponse
    {
        $planning = $planningRepository->find($id);
        if (!$planning) {
            return $this->json(['error' => 'Planning non trouvé'], 404);
        }

        $date = $planning->getDate();
        $typeShift = $planning->getTypeShift();
        $city = $request->query->get('city', 'Tunis,tn');
        
        // Récupérer la météo pour cette date
        $meteo = $this->getWeatherForDate($date, $city);
        
        // Adapter les conseils selon le shift
        $advice = $this->getAdviceForShift($meteo, $typeShift);
        
        return $this->json([
            'success' => true,
            'data' => [
                'planning_id' => $id,
                'date' => $date->format('d/m/Y'),
                'shift' => $typeShift,
                'meteo' => $meteo,
                'conseils' => $advice,
                'recommandation' => $this->getWorkRecommendation($meteo)
            ]
        ]);
    }

    // ==================== 2. PRÉVISIONS POUR LA SEMAINE ====================
    #[Route('/week', name: 'api_meteo_week', methods: ['GET'])]
    public function weekForecast(Request $request): JsonResponse
    {
        $city = $request->query->get('city', 'Tunis,tn');
        
        $forecast = $this->getWeeklyForecast($city);
        
        return $this->json([
            'success' => true,
            'city' => $city,
            'forecast' => $forecast
        ]);
    }

    // ==================== 3. MÉTÉO ACTUELLE ====================
    #[Route('/now', name: 'api_meteo_now', methods: ['GET'])]
    public function currentWeather(Request $request): JsonResponse
    {
        $city = $request->query->get('city', 'Tunis,tn');
        
        $weather = $this->getCurrentWeather($city);
        
        return $this->json([
            'success' => true,
            'city' => $city,
            'weather' => $weather
        ]);
    }

    // ==================== APPELS API OPENWEATHERMAP ====================
    
    private function getWeatherForDate(\DateTime $date, string $city): array
    {
        try {
            // Pour les dates passées ou futures, on prend la météo actuelle
            // OpenWeatherMap ne donne pas d'historique gratuit
            return $this->getCurrentWeather($city);
        } catch (\Exception $e) {
            return $this->getFallbackWeather();
        }
    }
    
    private function getCurrentWeather(string $city): array
    {
        try {
            $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$this->apiKey}&units=metric&lang=fr";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            return [
                'temperature' => round($data['main']['temp']),
                'condition' => $this->translateCondition($data['weather'][0]['description']),
                'humidity' => $data['main']['humidity'],
                'wind_speed' => round($data['wind']['speed']),
                'icon' => $this->getIconFromCode($data['weather'][0]['icon']),
                'temp_min' => round($data['main']['temp_min']),
                'temp_max' => round($data['main']['temp_max'])
            ];
        } catch (\Exception $e) {
            return $this->getFallbackWeather();
        }
    }
    
    private function getWeeklyForecast(string $city): array
    {
        try {
            $url = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$this->apiKey}&units=metric&lang=fr&cnt=7";
            $response = $this->client->get($url);
            $data = json_decode($response->getBody(), true);
            
            $forecast = [];
            $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            
            foreach ($data['list'] as $index => $item) {
                if ($index >= 7) break;
                $date = new \DateTime($item['dt_txt']);
                $forecast[] = [
                    'day' => $days[$date->format('N') - 1],
                    'date' => $date->format('d/m'),
                    'temp_min' => round($item['main']['temp_min']),
                    'temp_max' => round($item['main']['temp_max']),
                    'condition' => $this->getIconFromCode($item['weather'][0]['icon']),
                    'advice' => $this->getDailyAdvice($item['main']['temp_max'])
                ];
            }
            
            return $forecast;
        } catch (\Exception $e) {
            return $this->getFallbackWeeklyForecast();
        }
    }
    
    private function getFallbackWeather(): array
    {
        return [
            'temperature' => rand(18, 28),
            'condition' => '☀️ Ensoleillé',
            'humidity' => rand(40, 70),
            'wind_speed' => rand(5, 15),
            'icon' => '☀️',
            'temp_min' => rand(12, 18),
            'temp_max' => rand(25, 32)
        ];
    }
    
    private function getFallbackWeeklyForecast(): array
    {
        $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $forecast = [];
        for ($i = 0; $i < 7; $i++) {
            $date = (new \DateTime())->modify("+$i days");
            $forecast[] = [
                'day' => $days[$i],
                'date' => $date->format('d/m'),
                'temp_min' => rand(12, 18),
                'temp_max' => rand(22, 30),
                'condition' => ['☀️', '⛅', '🌧️', '🌬️'][rand(0, 3)],
                'advice' => $this->getDailyAdvice(rand(22, 30))
            ];
        }
        return $forecast;
    }
    
    private function translateCondition(string $description): string
    {
        $conditions = [
            'ciel dégagé' => '☀️ Ciel dégagé',
            'peu nuageux' => '⛅ Peu nuageux',
            'nuageux' => '☁️ Nuageux',
            'couvert' => '☁️ Couvert',
            'pluie légère' => '🌧️ Pluie légère',
            'pluie modérée' => '🌧️ Pluie',
            'pluie forte' => '🌧️ Forte pluie',
            'orage' => '⛈️ Orage',
            'neige' => '❄️ Neige',
            'brume' => '🌫️ Brume'
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
            '50d' => '🌫️', '50n' => '🌫️'
        ];
        return $icons[$iconCode] ?? '🌡️';
    }
    
    private function getAdviceForShift(array $meteo, string $shift): array
    {
        $temp = $meteo['temperature'];
        $condition = $meteo['condition'];
        
        $advice = [];
        
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
            case 'JOUR':
                $advice[] = '☀️ Shift de jour : protection solaire recommandée';
                break;
            case 'SOIR':
                $advice[] = '🌆 Shift de soir : lampe frontale recommandée';
                break;
            case 'NUIT':
                $advice[] = '🌙 Shift de nuit : se couvrir, températures plus fraîches';
                break;
            case 'CONGE':
                $advice[] = '🏖️ Bonne journée de congé !';
                break;
        }
        
        return $advice;
    }
    
    private function getWorkRecommendation(array $meteo): string
    {
        $temp = $meteo['temperature'];
        $condition = $meteo['condition'];
        
        if ($temp > 35) {
            return '⚠️ ALERTE : Canicule - Reporter les tâches extérieures';
        }
        if ($temp < 0) {
            return '⚠️ ALERTE : Gel - Prudence sur la route';
        }
        if (strpos($condition, 'Pluie') !== false) {
            return '🌧️ Pluie - Privilégier le travail en intérieur';
        }
        if (strpos($condition, 'Orage') !== false) {
            return '⚠️ Orage - Éviter les déplacements';
        }
        
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