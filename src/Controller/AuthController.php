<?php

namespace App\Controller;

use App\Entity\User;
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
    private $transactionFunction;
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
        TransactionFunction  $transactionFunction,

    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->myFunction = $myFunction;
        $this->transactionFunction = $transactionFunction;
        $this->passwordEncoder = $passwordEncoder;
        $this->user = $user;
        $this->jwt = $jwt;
        $this->jwtRefresh = $jwtRefresh;
        $this->paginator = $paginator;

        $this->validator = $validator;
        $this->mailer = $mailer;
    }


    /**
     * @Route("/auth/user", name="authUser", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function authUser(Request $request)
    {
        $data = $request->toArray();

        if (empty($data['username']) || empty($data['password'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez préciser votre username et mot de passe.');
        }

        $username = $data['username'];
        $password = $data['password'];
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);


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

        if (empty($data['username']) || empty($data['password'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez préciser votre nom, prénom, numéro de téléusername et mot de passe.');
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
        $keySecret = $this->createUniqueUid();
        // $keySecret = $this->generateKeySecret($username, $password);

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
