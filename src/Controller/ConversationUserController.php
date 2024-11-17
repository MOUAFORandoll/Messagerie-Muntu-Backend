<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Symfony\Component\Routing\Annotation\Route;
use App\FunctionU\MyFunction;
use App\Entity\User;
use App\Entity\Follow;


use App\Entity\MessageUser;
use App\Entity\MessageObject;
use App\Entity\ConversationUser;
use App\Entity\TypeObject;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Response\CustomJsonResponse;

class ConversationUserController extends AbstractController
{

    private $em;
    private   $serializer;
    private $clientWeb;


    private
        $transactionFunction;
    private $myFunction;
    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        HttpClientInterface $clientWeb,
        MyFunction
        $myFunction,




    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->myFunction = $myFunction;

        $this->clientWeb = $clientWeb;
    }
    /**
     * @Route("/message/status/{id}", name="updateMessageStatus", methods={"PATCH"})
     */
    public function updateMessageStatus(Request $request, $id): CustomJsonResponse
    {
        $message = $this->em->getRepository(MessageUser::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse(null, 404, 'Message non trouvé');
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!in_array($newStatus, [0, 1, 2])) {
            return new CustomJsonResponse(null, 203, 'Statut invalide. Les valeurs autorisées sont 0 (envoyé), 1 (reçu) ou 2 (lu).');
        }

        $message->setStatus($newStatus);
        $this->em->flush();
        $messageSend = $this->myFunction->formatMessageUser($message);
        return new CustomJsonResponse($messageSend, 200, 'Statut mis à jour avec succès');
    }


    /**
     * @Route("/messages", name="newMessageUser", methods={"POST"})
     */
    public function newMessageUser(Request $request)
    {


        $data = [
            'emetteur_id' => $request->get('emetteur_id'),
            'message' => $request->get('message'),
            'receiver_id' => $request->get('receiver_id'),

            'typeFile' => $request->get('typeFile'),
            'message_target' => $request->get('message_target'),
        ];

        $requiredFields = [
            'emetteur_id',
            'message',
            'receiver_id'
        ];

        if (!$this->myFunction->checkRequiredFields($data, $requiredFields)) {
            return new CustomJsonResponse(null, 203, 'Vérifiez votre requête');
        }

        $emetteurId = $data['emetteur_id'];
        $messageText = $data['message'];
        $receiverId = $data['receiver_id'];

        $sender = $this->em->getRepository(User::class)->findOneBy(['id' => $emetteurId]);
        if (!$sender) {
            return new CustomJsonResponse(null, 203, 'Vous n\'êtes pas autorisé, vous ne pouvez pas poursuivre l\'opération');
        }

        $receiver = $this->em->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            return new CustomJsonResponse(null, 203, 'Le destinataire n\'existe pas');
        }

        $conversation = $this->em->getRepository(ConversationUser::class)->findOneBy([
            'first' => $sender,
            'second' => $receiver
        ]);

        if (!$conversation) {
            $conversation = $this->em->getRepository(ConversationUser::class)->findOneBy([
                'first' => $receiver,
                'second' => $sender
            ]);
        }

        if (!$conversation) {
            $conversation = new ConversationUser();
            $conversation->setFirst($sender);
            $conversation->setSecond($receiver);
            $this->em->persist($conversation);
        }

        $message = new MessageUser();
        $message->setValeur(rtrim($messageText));
        $message->setEmetteur($sender);
        $message->setConversation($conversation);
        $message->setStatus(0);
        if ((isset($data['message_target']) &&  $data['message_target'] != null)) {
            $message_target
                = $data['message_target'];
            $messageTarget
                = $this->em->getRepository(MessageUser::class)->findOneBy(['id' => $message_target]);
            if ($messageTarget) {

                $message->setMessageTarget($messageTarget);
            }
        }
        $fichiers = [];
        $this->em->persist($message);
        $this->em->flush();
        if (isset($data['typeFile']) && $data['typeFile'] != null) {
            $typeFile
                = $data['typeFile'];

            $fichiers =   $this->creatMessageObject($message, $request, $typeFile);
        }
        $this->em->persist($message);
        if (isset($data['typeObject']) && $data['typeObject'] != null) {
            $typeObjectId
                = $data['typeObject'];
            $typeObject = $this->em->getRepository(TypeObject::class)->findOneBy([

                'id' => $typeObjectId
            ]);
            $fichiers =   $this->creatMessageObject($message, $request, $typeObject);
        }
        $this->em->flush();

        $messageSend =
            $this->myFunction->formatMessageUser($message);

        // Vous pouvez implémenter une méthode pour émettre le message en temps réel si nécessaire
        // $this->myFunction->emitNewMessage($receiver->getId(), $messageSend);

        return new JsonResponse($messageSend, 201,);
    }
    /**
     * @Route("/conversations-messages", name="getMessageForConversation", methods={"GET"})
     */
    public function getMessageForConversation(Request $request): CustomJsonResponse
    {
        $data = [
            'emetteur_id' => $request->get('emetteur_id'),

            'recepteur_id' => $request->get('recepteur_id'),
        ];

        $requiredFields = [
            'emetteur_id',

            'recepteur_id'
        ];

        if (!$this->myFunction->checkRequiredFields($data, $requiredFields)) {
            return new CustomJsonResponse(null, 203, 'Vérifiez votre requête');
        }
        $emetteurId = $data['emetteur_id'];

        $receiverId = $data['recepteur_id'];

        $conversation = $this->em->getRepository(ConversationUser::class)->findOneBy([
            'first' => $emetteurId,
            'second' => $receiverId
        ]);

        if (!$conversation) {
            $conversation = $this->em->getRepository(ConversationUser::class)->findOneBy([
                'first' => $receiverId,
                'second' => $emetteurId
            ]);
        }
        if (!$conversation) {
            return new CustomJsonResponse([
                'total' => 0,
                'page' => 0,
                'data' => []
            ],  200, 'Conversation non trouvée');
        }
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $messagesQuery = $this->em->getRepository(MessageUser::class)->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery();

        $paginator = new Paginator($messagesQuery);
        $totalMessages = count($paginator);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $messagesData = array_map(function ($message) {
            return $this->myFunction->formatMessageUser($message);
        }, iterator_to_array($paginator));

        return new CustomJsonResponse([
            'total' => $totalMessages,
            'page' => $page,
            'data' => $messagesData
        ], 200, 'Messages récupérés avec succès');
    }
    /**
     * @Route("/message/{id}", name="deleteMessageUser", methods={"DELETE"})
     */
    public function deleteMessageUser($id)
    {
        $message = $this->em->getRepository(MessageUser::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse(null, 404, 'Message non trouvé');
        }

        $message->setDeletedAt();
        $this->em->persist($message);
        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageUser($message);

        return new CustomJsonResponse($messageSend, 200, 'Message supprimé avec succès');
    }

    /**
     * @Route("/message/{id}", name="updateMessageUser", methods={"PATCH"})
     */
    public function updateMessageUser(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['message'])) {
            return new CustomJsonResponse(null, 203, 'Veuillez renseigner le message');
        }

        $message = $this->em->getRepository(MessageUser::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse(null, 404, 'Message non trouvé');
        }

        $message->setValeur($data['message']);

        $this->em->persist($message);
        $this->em->flush();

        $messageSend =
            $this->myFunction->formatMessageUser($message);

        return new CustomJsonResponse($messageSend, 200, 'Message mis à jour avec succès');
    }

    /**
     * @Route("/message/{id}", name="getMessageUser", methods={"GET"})
     */
    public function getMessageUser($id)
    {
        $message = $this->em->getRepository(MessageUser::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse(null, 404, 'Message non trouvé');
        }

        $messageSend =
            $this->myFunction->formatMessageUser($message);

        return new CustomJsonResponse($messageSend, 200, 'Message récupéré avec succès');
    }
    /**
     * @Route("/list-conversations", name="getUserConversations", methods={"GET"})
     */
    public function getUserConversations(Request $request): CustomJsonResponse
    {
        $userId = $request->get('userId');
        $user = $this->em->getRepository(User::class)->find($userId);
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        if (!$user) {
            return new CustomJsonResponse(null, 404, 'Utilisateur non trouvé');
        }

        $conversationsAsFirst = $this->em->getRepository(ConversationUser::class)->findBy([
            'first' => $user
        ]);

        $conversationsAsSecond = $this->em->getRepository(ConversationUser::class)->findBy([
            'second' => $user
        ]);

        $conversations = array_merge($conversationsAsFirst, $conversationsAsSecond);

        $conversationsData = [];


        $offset = ($page - 1) * $limit;
        $paginatedConversations = array_slice($conversations, $offset, $limit);





        foreach ($paginatedConversations as $conversation) {
            $otherUser = $conversation->getFirst()->getId() === $user->getId()
                ? $conversation->getSecond()
                : $conversation->getFirst();
            $existingFollow = $this->em->getRepository(Follow::class)->findOneBy([
                'follower' => $user,
                'following' => $otherUser
            ]) ??
                $this->em->getRepository(Follow::class)->findOneBy([
                    'follower' => $otherUser,
                    'following' => $user
                ]);
            if ($existingFollow) {


                $contact = [
                    'id' => $otherUser->getId(),
                    'username' => $existingFollow->getNameContact(),
                    'nameContact' => $existingFollow->getNameContact(),
                    'surnameContact' => $existingFollow->getSurnameContact(),
                    'phone' => $otherUser->getPhone(),
                    'codePhone' => $otherUser->getCodePhone()
                ];
            } else {
                $contact = [
                    'id' => $otherUser->getId(),
                    'username' => $otherUser->getNameUser(),
                    'nameContact' => $otherUser->getNameUser(),
                    'surnameContact' => "",
                    'phone' => $otherUser->getPhone(),
                    'codePhone' => $otherUser->getCodePhone()
                ];
            }
            $conversationsData[] = [
                'id' => $conversation->getId(),
                'otherUser' => $contact,
                'lastMessage' => $this->getLastMessage($conversation)
            ];

            $paginatedResults = [
                'total' => count($conversations),
                'page' => $page,
                'data' => $conversationsData
            ];

            return new CustomJsonResponse($paginatedResults, 200, 'Conversations récupérées avec succès');
        }
    }

    private function getLastMessage(ConversationUser $conversation): ?array
    {
        $lastMessage = $this->em->getRepository(MessageUser::class)->findOneBy(
            ['conversation' => $conversation],
            ['createdAt' => 'DESC']
        );

        if (!$lastMessage) {
            return null;
        }

        return [
            'id' => $lastMessage->getId(),
            'content' => $lastMessage->getValeur(),
            'createdAt' => $lastMessage->getCreatedAt()->format('Y-m-d H:i:s'),
            'senderId' => $lastMessage->getEmetteur()->getId()
        ];
    }


    private function creatMessageObject(MessageUser $message, Request $file_send, TypeObject $typeObject)
    {

        $fichiers = [];
        for ($i = 0; $i < count($file_send->files); $i++) {

            $file = $file_send->files->get('file' . $i);

            if ($file) {

                $newFilenameData =
                    $this->myFunction->getUniqueFileNameMessage($file->guessExtension()) . '.' . $file->guessExtension();


                try {

                    $file->move(
                        $this->getParameter('call_center'),
                        $newFilenameData
                    );

                    $fichierObject = new MessageObject();
                    $fichierObject->setSrc($newFilenameData);
                    // $fichierObject->setMessage($message);
                    $fichierObject->setTypeObject($typeObject);
                    $this->em->persist($fichierObject);
                    $this->em->flush();

                    $fichiers[] = [
                        'src' =>    $_ENV['BACK_END_URL'] . '/images/call_center/' . $newFilenameData,
                    ];
                } catch (FileException $e) {
                    break;
                }
            }
        }


        return
            $fichiers;
    }
}
