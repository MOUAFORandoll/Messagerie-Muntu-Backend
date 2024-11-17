<?php

namespace App\FunctionU;

use App\Entity\ColisObject;
use App\Entity\Livraison;
use App\Entity\LivraisonKey;
use App\Entity\LivraisonOrdonnanceKey;
use App\Entity\Medicament;
use App\Entity\MedicamentPharmacie;
use App\Entity\Ordonnance;
use App\Entity\Pharmacie;
use App\Entity\OrdonnanceMedicament;
use App\Entity\Panier;
use App\Entity\UserObject;
use App\Entity\Produit;
use App\Entity\User;
use App\Entity\Compte;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTService;
use App\Entity\PointLocalisation;
use App\Entity\ListProduitPanier;
use App\Entity\ProduitObject;
use App\Entity\Market;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Exception;



use App\Entity\CallCenter;
use App\Entity\LivreurLivraisonPosition;
use App\Entity\Message;
use App\Entity\MessageUser;

use App\Entity\MessageObject;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserProvider;
use Symfony\Component\Mailer\MailerInterface;
use Swift_Mailer;
use Swift_SmtpTransport;
use Symfony\Component\HttpFoundation\File\File as FileFile;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Token;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class MyFunction
{

    // commande pour activer l'envoi de mail php bin/console messenger:consume async
    public $emetteur = 'admin@prikado.com';

    private $security;

    private $mailer;
    private $em;
    private $client;
    private $jwtManager;
    // BACK_END_URL =
    // 'http://192.168.1.102:8000';
    const
        PAGINATION = 14;
    public function __construct(
        EntityManagerInterface $em,
        HttpClientInterface $client,
        MailerInterface $mailer,
        JWTTokenManagerInterface $jwtManager,
        Security $security

    ) {

        $this->jwtManager = $jwtManager;

        $this->client =
            $client;
        $this->mailer =
            $mailer;
        $this->security
            = $security;
        $this->em = $em;
    }
    public function  getBackendUrl()
    {
        return    $_ENV['BACK_END_URL'];
    }
    public function removeSpace(string $value)
    {
        return str_replace(' ', '', rtrim(trim($value)));
    }


    public function checkRequiredFields(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {


                return false;
            }
        }
        return true;
    }

    public function getUniqueFileNameMessage($extension)
    {


        $chaine = 'file_';
        $listeCar = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = mb_strlen($listeCar, '8bit') - 1;
        for ($i = 0; $i < 5; ++$i) {
            $chaine .= $listeCar[random_int(0, $max)];
        }
        $ExistCode = $this->em->getRepository(MessageObject::class)->findOneBy(['src' => $chaine . $extension]);
        if ($ExistCode) {
            return
                $this->getUniqueFileNameMessage($extension);
        } else {
            return $chaine;
        }
    }
    public function Socekt_Emit($canal, $data)
    {



        $first =   $this->client->request('GET',     $_ENV['SOCKET_SERVER_URL']  . "/socket.io/?EIO=4&transport=polling&t=N8hyd6w");
        $content = $first->getContent();
        $index = strpos($content, 0);
        $res = json_decode(substr($content, $index + 1), true);
        $sid = $res['sid'];
        $this->client->request('POST',    $_ENV['SOCKET_SERVER_URL']  . "/socket.io/?EIO=4&transport=polling&sid={$sid}", [
            'body' => '40'
        ]);

        $dataEmit = [$canal, json_encode($data)];

        // $this->client->request('POST',    $_ENV['SOCKET_SERVER_URL']  ."/socket.io/?EIO=4&transport=polling&sid={$sid}", [
        //     'body' => sprintf('42["%s", %s]', $userID, json_encode($dataEmit))
        // ]);
        // $this->client->request('POST',    $_ENV['SOCKET_SERVER_URL']  ."/socket.io/?EIO=4&transport=polling&sid={$sid}", [
        //     'body' => sprintf('42%s',  json_encode($dataSign))
        // ]);
        $this->client->request('POST',    $_ENV['SOCKET_SERVER_URL']  . "/socket.io/?EIO=4&transport=polling&sid={$sid}", [
            'body' => sprintf('42%s',  json_encode($dataEmit))
        ]);
    }

    function formatMessageUser(MessageUser $message)
    {
        return
            [
                'id' => $message->getId(),
                'message' => $message->getValeur(),
                'dateSend' => $message->getCreatedAt()->format('Y-m-d'),
                'heureSend' => $message->getCreatedAt()->format('H:i'),
                'status' => $message->getStatus(),
                'emetteur' => [
                    'id' => $message->getEmetteur()->getId(),
                    'username' => $message->getEmetteur()->getUsername()
                ],
                'receiver' => [
                    'id' => $message->getConversation()->getSecond()->getId(),
                    'username' => $message->getConversation()->getSecond()->getUsername()
                ],
                'messageTarget' => $message->getMessageTarget() != null ? $this->formatMessageUser($message->getMessageTarget(),) : null,
                'attachFile' =>  $message->getDeletedAt() != null ? [] : $this->getAttachMessageObject($message)

            ];
    }


    public function getAttachMessageObject(MessageUser $message)
    {
        $objects = $message->getMessageObjects();
        $fichiers = [];
        foreach ($objects as $ob) {
            $fichiers[] = [
                'src' =>    $_ENV['BACK_END_URL'] . '/images/call_center/' . $ob->getSrc(),
            ];
        }
        return $fichiers;
    }
    function formatMessageCanal(Message  $message)
    {
        return
            [
                'id' => $message->getId(),
                'message' => $message->getValeur(),
                'dateSend' => $message->getCreatedAt()->format('Y-m-d'),
                'heureSend' => $message->getCreatedAt()->format('H:i'),
                'status' => $message->getStatus(),
                'emetteur' => [
                    'id' => $message->getEmetteurCanal()->getMuntu()->getId(),
                    'username' => $message->getEmetteurCanal()->getMuntu()->getUsername()
                ],

            ];
    }


    function formatMessageGroupe(Message  $message)
    {
        return
            [
                'id' => $message->getId(),
                'message' => $message->getValeur(),
                'dateSend' => $message->getCreatedAt()->format('Y-m-d'),
                'heureSend' => $message->getCreatedAt()->format('H:i'),
                'status' => $message->getStatus(),
                'emetteur' => [
                    'id' => $message->getEmetteurCanal()->getMuntu()->getId(),
                    'username' => $message->getEmetteurCanal()->getMuntu()->getUsername()
                ],

            ];
    }









    // Socket emit



    public function emitcallCenterToAdmin($call_center_id, $message)
    {

        $this->Socekt_Emit(
            "service_client",
            [
                'typeUser' => 1,
                'call_center_id' => $call_center_id,
                'data'
                =>
                $message

            ]
        );
    }


    public function formatUser(User $user): array
    {


        $profile      = count($user->getUserObjects())  == 0 ? '' : $user->getUserObjects()->last()->getSrc();
        // $user->getUserObjects()[count($user->getUserObjects()) - 1]->getSrc();
        $userU = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),


            'date_created' => date_format($user->getCreatedAt(), 'Y-m-d H:i'),

        ];



        return  $userU;
    }

    public function requestUser(Request $request)
    {
        $user = $this->security->getUser();

        if ($user) {
            $userFound = $this->em->getRepository(User::class)->findOneBy(['id' => $user->getUsername()]);
            return $userFound;
        }
        return null;
    }
}
