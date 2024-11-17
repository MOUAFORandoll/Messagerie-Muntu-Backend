<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TypeUser;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Symfony\Component\Routing\Annotation\Route;
use App\FunctionU\MyFunction;
use App\FunctionU\TransactionFunction;
use App\Entity\TypeParticipant;
use DateTime;
use Knp\Component\Pager\PaginatorInterface;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Response\CustomJsonResponse;
use Ramsey\Uuid\Uuid;

class AuthController extends AbstractController
{


    private $em;
    private   $serializer;
    private $mailer;
    private $user;
    private $passwordEncoder;
    private $jwt;
    private $jwtRefresh;
    private $validator;
    private $myFunction;
    private $paginator; 
    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        HttpClientInterface $user,
        JWTTokenManagerInterface $jwt,
        PaginatorInterface $paginator,
        RefreshTokenManagerInterface $jwtRefresh,
        UserPasswordHasherInterface    $passwordEncoder,
        ValidatorInterface
        $validator,
        MyFunction
        $myFunction, 

    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->myFunction = $myFunction; 
        $this->passwordEncoder = $passwordEncoder;
        $this->user = $user;
        $this->jwt = $jwt;
        $this->jwtRefresh = $jwtRefresh;
        $this->paginator = $paginator;

        $this->validator = $validator;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/auth/user-exist-verify", name="authUserExistVerify", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function authUserExistVerify(Request $request)
    {
        $data = $request->toArray();

        if (empty($data['identifiant'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez préciser votre numéro de téléphone ou votre adresse e-mail.');
        }

        $identifiant = $data['identifiant'];
        $user = null;

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $identifiant]) ?? $this->em->getRepository(User::class)->findOneBy(['email' => $identifiant]);


        if (!$user) {
            return new CustomJsonResponse([
                'exist_status' => false
            ], 200, 'Ce client n\'existe pas');
        }

        return new CustomJsonResponse([
            'exist_status' => true
        ], 200, 'Ce client existe');
    }



    /**
     * @Route("/auth/user", name="authUser", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function authUser(Request $request)
    {
        $data = $request->toArray();

        if (empty($data['identifiant']) || empty($data['password'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez préciser votre identifiant et mot de passe.');
        }

        $identifiant = $data['identifiant'];
        $password = $data['password'];
        $user = $this->em->getRepository(User::class)->findOneBy(['anonymousId' => $identifiant]) ?? $this->em->getRepository(User::class)->findOneBy(['email' => $identifiant]);

        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Ce client n\'existe pas');
        }

        if (!password_verify($password, $user->getPassword())) {
            return new CustomJsonResponse(null, 203, 'Mot de passe incorrect');
        }

        $infoUser = $this->createNewJWT($user);
        $tokenAndRefresh = json_decode($infoUser->getContent(), true);
        $userFormat = $this->myFunction->formatUser($user);
        return new CustomJsonResponse([
            'token' => $tokenAndRefresh['token'],
            'refreshToken' => $tokenAndRefresh['refreshToken'],
            'user' => $userFormat
        ], 200, 'Authentification réussie');
    }

    /**
     * @Route("/auth/create-user", name="createUser", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function createUser(Request $request)
    {
        $data = $request->toArray();

        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez  votre nom d\'utilisateur, adresse mail et mot de passe.');
        }


        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($user) {
            return new CustomJsonResponse(null, 203, 'Numéro de téléusername déjà utilisé');
        }

        $userEmail = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($userEmail) {
            return new CustomJsonResponse(null, 203, 'Adresse email déjà utilisée');
        }

        $user = new User();

        $user->setusername($username);

        $encodedPassword = $this->passwordEncoder->hashPassword($user, $password);
        $user->setPassword($encodedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $infoUser = $this->createNewJWT($user);
        $tokenAndRefresh = json_decode($infoUser->getContent(), true);

        $userFormat = $this->myFunction->formatUser($user);

        return new CustomJsonResponse([
            'user' => $userFormat,
            'token' => $tokenAndRefresh['token'],
            'refreshToken' => $tokenAndRefresh['refreshToken'],
        ], 201, 'Utilisateur créé avec succès');
    }

    /**
     * @Route("/auth/user/anonymous-id", name="createAnonymousId", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function createAnonymousId(Request $request)
    {

        // Génération de l'anonymousId
        $anonymousId = 'M' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT) . 'NA' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Vérification de l'unicité de l'anonymousId
        $isUnique = false;
        while (!$isUnique) {
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['anonymousId' => $anonymousId]);
            if (!$existingUser) {
                $isUnique = true;
            } else {
                $anonymousId = 'M' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT) . 'NA' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
        }



        return new CustomJsonResponse([
            'anonymousId' => $anonymousId,
        ], 200, 'Identifiant anonyme généré avec succès');
    }


    /**
     * @Route("/auth/user/create-anonymous-user", name="createAnonymousUser", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function createAnonymousUser(Request $request)
    {
        $data = $request->toArray();

        if (empty($data['password']) || empty($data['anonymousId'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez fournir un mot de passe.');
        }



        $anonymousId = $data['anonymousId'];
        $password = $data['password'];

        $user = $this->em->getRepository(User::class)->findOneBy(['anonymousId' => $anonymousId]);
        if ($user) {
            return new CustomJsonResponse(null, 203, 'Cet identifiant anonyme est déjà utilisé');
        }

        $user = new User();

        $user->setAnonymousId($anonymousId);

        $encodedPassword = $this->passwordEncoder->hashPassword($user, $password);
        $user->setPassword($encodedPassword);
        $user->setusername($anonymousId);

        $this->em->persist($user);
        $this->em->flush();

        $infoUser = $this->createNewJWT($user);
        $tokenAndRefresh = json_decode($infoUser->getContent(), true);

        $userFormat = $this->myFunction->formatUser($user);

        return new CustomJsonResponse([
            'user' => $userFormat,
            'token' => $tokenAndRefresh['token'],
            'refreshToken' => $tokenAndRefresh['refreshToken'],
        ], 201, 'Utilisateur anonyme créé avec succès');
    }

    /**
     * @Route("/auth/user/new-password", name="newPassword", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function newPassword(Request $request)
    {
        $dataRequest = $request->toArray();

        if (empty($dataRequest['password']) || empty($dataRequest['username'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez préciser votre numéro de téléusername ou votre adresse e-mail et le nouveau mot de passe');
        }

        $username = $dataRequest['username'];
        $password = $dataRequest['password'];

        $user =  $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            return new CustomJsonResponse(null, 203, 'Cet utilisateur n\'existe pas');
        }

        $passwordN = $this->passwordEncoder->hashPassword($user, $password);
        $user->setPassword($passwordN);
        $this->em->persist($user);
        $this->em->flush();

        $infoUser = $this->createNewJWT($user);
        $tokenAndRefresh = json_decode($infoUser->getContent(), true);

        $userFormat = $this->myFunction->formatUser($user);
        return new CustomJsonResponse([
            'token' => $tokenAndRefresh['token'],
            'refreshToken' => $tokenAndRefresh['refreshToken'],
            'user' => $userFormat
        ], 200, 'Mot de passe changé avec succès');;
    }

    /**
     * @Route("/user/me", name="me", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request)
    {
        $user = $this->myFunction->requestUser($request);

        if (!$user) {
            new CustomJsonResponse(
                [
                    'message' => 'Desolez l\'utilisateur en question a des contraintes',

                ],
                203,
                'Utilisateur introuvable'

            );
        }
        $userFormat = $this->myFunction->formatUser($user);
        return new CustomJsonResponse(
            [

                'user' => $userFormat
            ],
            200,
            'Utilisateur récupéré avec succès'
        );
    }
    /**
     * @Route("/auth/user/social", name="authOrCreateSocialUser", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function authOrCreateSocialUser(Request $request)
    {
        $data = $request->toArray();

        if (empty($data['email']) || empty($data['nom'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez fournir un email et un nom.');
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Créer un nouvel utilisateur
            $user = new User();
            $user->setUsername($data['nom']);
            $user->setEmail($data['email']);
            // $user->setEmail($data['email']);
            $password = bin2hex(random_bytes(8)); // Génère un mot de passe aléatoire
            $user->setPassword($this->passwordEncoder->hashPassword($user, $password));


            if (isset($data['isSocialFacebook'])) {
                $user->setIsSocialFacebook($data['isSocialFacebook']);
            }

            if (isset($data['isSocialGoogle'])) {
                $user->setIsSocialGoogle($data['isSocialGoogle']);
            }

            $this->em->persist($user);
            $this->em->flush();

            $message = 'Nouvel utilisateur créé et authentifié avec succès';
        } else {
            if (
                (!$user->isIsSocialGoogle() && !$user->isIsSocialFacebook()) &&  !empty($user->getPassword())
            ) {
                return new CustomJsonResponse(null, 203, 'Vous avez déjà un compte avec mot de passe associé à cette adresse e-mail. Veuillez utiliser l\'authentification normale.');
            }
            $message = 'Utilisateur existant authentifié avec succès';
        }

        $infoUser = $this->createNewJWT($user);
        $tokenAndRefresh = json_decode($infoUser->getContent(), true);
        $userFormat = $this->myFunction->formatUser($user);

        return new CustomJsonResponse([
            'token' => $tokenAndRefresh['token'],
            'refreshToken' => $tokenAndRefresh['refreshToken'],
            'user' => $userFormat
        ], 200, $message);
    }


    /**
     * @Route("/user/search", name="searchUser", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function searchUser(Request $request): JsonResponse
    {
        $username = $request->query->get('username');

        if (!$username) {
            return new CustomJsonResponse(
                ['message' => 'Le paramètre username est requis'],
                400,
                'Paramètre manquant'
            );
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user) {
            return new CustomJsonResponse(
                ['message' => 'Aucun utilisateur trouvé avec ce nom d\'utilisateur'],
                404,
                'Utilisateur non trouvé'
            );
        }

        $userFormat = $this->myFunction->formatUser($user);
        return new CustomJsonResponse(
            ['user' => $userFormat],
            200,
            'Utilisateur trouvé avec succès'
        );
    }
    public function createNewJWT(User $user)
    {
        $token = $this->jwt->create($user);

        $datetime = new \DateTime();
        $datetime->modify('+2592000 seconds');

        $refreshToken = $this->jwtRefresh->create();

        $refreshToken->setUsername($user->getUsername());
        $refreshToken->setRefreshToken();
        $refreshToken->setValid($datetime);

        // Validate, that the new token is a unique refresh token
        $valid = false;
        while (false === $valid) {
            $valid = true;
            $errors = $this->validator->validate($refreshToken);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    if ('refreshToken' === $error->getPropertyPath()) {
                        $valid = false;
                        $refreshToken->setRefreshToken();
                    }
                }
            }
        }

        $this->jwtRefresh->save($refreshToken);

        return new JsonResponse([
            'token' => $token,
            'refreshToken' => $refreshToken->getRefreshToken()
        ], 200);
    }
    public function createUniqueUid(): string
    {
        do {
            $uuid = Uuid::uuid1()->toString();
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['keySecret' => $uuid]);
        } while ($existingUser);

        return $uuid;
    }

    /**
     * @Route("/auth/test", name="testEmail", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function testEmail(Request $request)
    {
        $user = $this->myFunction->requestUser($request);
        return new JsonResponse(['user' => $user]);

        // Get the authenticated user
        // $user = $this->security->getUser();

        // if ($user) {
        //     // User is authenticated, and you can access the user object
        //     return new JsonResponse(['username' => $user->getUsername()]);
        // } else {
        //     // No user is authenticated
        //     return new JsonResponse(['message' => 'No user is authenticated']);
        // }

        // $email = 'hari.randoll5.0@gmail.com';
        // $subject = 'Test d\'envoi d\'e-mail';
        // $message = 'Ceci est un e-mail de test envoyé depuis l\'API.';

        // $result = $this->myFunction->sendMail($email, $subject, $message);
        // if ($result) {
        //     return new JsonResponse($result, 200,);
        // } else {
        //     return new JsonResponse($result, 500,);
        // }
    }
}
