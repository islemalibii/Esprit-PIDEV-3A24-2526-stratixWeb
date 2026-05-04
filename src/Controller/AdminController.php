<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(UtilisateurRepository $repo): Response
    {
        /** @var \App\Entity\Utilisateur $currentUser */
        $currentUser = $this->getUser();
        $role = $currentUser->getRole();

        // Responsables → redirection directe vers tâches
        if (!in_array($role, ['admin'])) {
            return $this->redirectToRoute('app_tache_index');
        }

        $total  = $repo->count([]);
        $actifs = $repo->count(['statut' => 'actif']);
        $locked = $repo->count(['account_locked' => true]);
        $admins = $repo->count(['role' => 'admin']);

        $rolesRaw = $repo->createQueryBuilder('u')
            ->select('u.role, COUNT(u.id) as total')
            ->groupBy('u.role')
            ->getQuery()->getResult();

        $roles = [];
        foreach ($rolesRaw as $r) {
            $roles[$r['role']] = $r['total'];
        }

        $recent = $repo->findBy([], ['id' => 'DESC'], 8);
        $lockedUsers = $repo->findBy(['account_locked' => true], ['id' => 'DESC'], 5);

        // Stats émotions du jour
        $allUsers = $repo->findBy(['statut' => 'actif']);
        $emotionStats = ['happy' => 0, 'neutral' => 0, 'sad' => 0, 'angry' => 0, 'fearful' => 0, 'surprised' => 0, 'disgusted' => 0, 'unknown' => 0];
        foreach ($allUsers as $u) {
            $e = $u->getLastEmotion() ?? 'unknown';
            if (isset($emotionStats[$e])) $emotionStats[$e]++;
            else $emotionStats['unknown']++;
        }
        $totalEmotions = array_sum($emotionStats);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total'       => $total,
                'actifs'      => $actifs,
                'locked'      => $locked,
                'admins'      => $admins,
                'taux_actifs' => $total > 0 ? round($actifs / $total * 100) : 0,
            ],
            'roles'       => $roles,
            'recent'      => $recent,
            'lockedUsers' => $lockedUsers,
            'emotionStats'   => $emotionStats,
            'totalEmotions'  => $totalEmotions,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UtilisateurRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role   = $request->query->get('role', '');
        $statut = $request->query->get('statut', '');

        $qb = $repo->createQueryBuilder('u');
        if ($search) {
            $qb->andWhere('u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s')
               ->setParameter('s', "%$search%");
        }
        if ($role)   { $qb->andWhere('u.role = :role')->setParameter('role', $role); }
        if ($statut) { $qb->andWhere('u.statut = :statut')->setParameter('statut', $statut); }

        $users = $qb->orderBy('u.id', 'DESC')->getQuery()->getResult();

        return $this->render('admin/users.html.twig', [
            'users'  => $users,
            'search' => $search,
            'role'   => $role,
            'statut' => $statut,
        ]);
    }

    #[Route('/users/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }
            $user->setDateAjout(new \DateTime());

            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile && $request->request->get('face_validated') === '1') {
                $filename = uniqid('avatar_') . '.' . ($avatarFile->guessExtension() ?? 'jpg');
                /** @var string $projectDir */
                $projectDir = $this->getParameter('kernel.project_dir');
                $avatarFile->move($projectDir . '/public/images/avatar', $filename);
                $user->setAvatar($filename);
            }

            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', ['form' => $form, 'title' => 'Nouvel utilisateur', 'editUser' => null]);
    }

    #[Route('/users/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Utilisateur $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UtilisateurType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }

            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile && $request->request->get('face_validated') === '1') {
                $filename = uniqid('avatar_') . '.' . ($avatarFile->guessExtension() ?? 'jpg');
                /** @var string $projectDir */
                $projectDir = $this->getParameter('kernel.project_dir');
                $avatarFile->move($projectDir . '/public/images/avatar', $filename);
                $user->setAvatar($filename);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', ['form' => $form, 'title' => 'Modifier l\'utilisateur', 'editUser' => $user]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Utilisateur $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), (string)$request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/toggle-lock', name: 'admin_user_toggle_lock', methods: ['POST'])]
    public function toggleLock(Utilisateur $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$user->getId(), (string)$request->request->get('_token'))) {
            $user->setAccountLocked(!$user->isAccountLocked());
            if ($user->isAccountLocked()) {
                $user->setLockedAt(new \DateTimeImmutable()); // date du verrouillage
            } else {
                $user->setFailedLoginAttempts(0);
                $user->setLockedUntil(null);
                $user->setLockedAt(null);
            }
            $em->flush();
            $this->addFlash('success', 'Statut du compte mis à jour.');
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/change-role', name: 'admin_user_change_role', methods: ['POST'])]
    public function changeRole(Utilisateur $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('role'.$user->getId(), (string)$request->request->get('_token'))) {
            $role = (string)$request->request->get('role');
            if (in_array($role, ['admin', 'employe', 'responsable_rh', 'responsable_projet', 'responsable_production', 'ceo'])) {
                $user->setRole($role);
                $em->flush();
                $this->addFlash('success', 'Rôle mis à jour.');
            }
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/qrcode', name: 'admin_user_qrcode', methods: ['GET'])]
    public function qrcode(Utilisateur $user): Response
    {
        $data = (string)json_encode([
            'id'         => $user->getId(),
            'nom'        => $user->getNom(),
            'prenom'     => $user->getPrenom(),
            'email'      => $user->getEmail(),
            'poste'      => $user->getPoste(),
            'department' => $user->getDepartment(),
            'role'       => $user->getRole(),
        ]);

        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($data);

        return new Response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    #[Route('/users/{id}/badge', name: 'admin_user_badge', methods: ['GET'])]
    public function badge(Utilisateur $user): Response
    {
        return $this->render('admin/user_badge.html.twig', ['user' => $user]);
    }

    #[Route('/organigramme', name: 'admin_organigramme')]
    public function organigramme(UtilisateurRepository $repo): Response
    {
        $users = $repo->findBy(['statut' => 'actif'], ['role' => 'ASC']);

        // Construire la hiérarchie
        $hierarchy = [
            'name' => 'Stratix',
            'title' => 'Entreprise',
            'avatar' => null,
            'children' => []
        ];

        $roleOrder = ['ceo', 'admin', 'responsable_rh', 'responsable_projet', 'responsable_production', 'employe'];
        $roleLabels = [
            'admin' => 'Administrateur',
            'ceo' => 'CEO',
            'responsable_rh' => 'Responsable RH',
            'responsable_projet' => 'Responsable Projet',
            'responsable_production' => 'Responsable Production',
            'employe' => 'Employé',
        ];

        $groups = [];
        foreach ($users as $u) {
            $role = $u->getRole();
            if (!isset($groups[$role])) {
                $groups[$role] = [];
            }
            $groups[$role][] = $u;
        }

        foreach ($roleOrder as $role) {
            if (empty($groups[$role])) continue;
            foreach ($groups[$role] as $u) {
                $node = [
                    'id'     => $u->getId(),
                    'name'   => $u->getPrenom() . ' ' . $u->getNom(),
                    'title'  => $roleLabels[$role],
                    'dept'   => $u->getDepartment() ?? '',
                    'avatar' => $u->getAvatar(),
                    'role'   => $role,
                ];
                $hierarchy['children'][] = $node;
            }
        }

        return $this->render('admin/organigramme.html.twig', [
            'hierarchy' => json_encode($hierarchy),
            'total'     => count($users),
        ]);
    }

    #[Route('/notifications/read', name: 'admin_notifications_read')]    public function markNotificationsRead(Request $request, \App\Repository\UtilisateurRepository $repo): Response
    {
        $session = $request->getSession();
        $newUsers = $repo->findBy([], ['id' => 'DESC'], 5);
        $locked   = $repo->findBy(['account_locked' => true], ['id' => 'DESC'], 5);
        $updated  = $repo->createQueryBuilder('u')
            ->where('u.updated_at IS NOT NULL')
            ->orderBy('u.updated_at', 'DESC')
            ->setMaxResults(5)->getQuery()->getResult();

        $readIds = array_merge(
            array_map(fn($u) => 'new_'.$u->getId(), $newUsers),
            array_map(fn($u) => 'lock_'.$u->getId(), $locked),
            array_map(fn($u) => 'edit_'.$u->getId(), $updated)
        );
        $session->set('notif_read_ids', $readIds);
        return $this->redirectToRoute('admin_dashboard');
    }
}