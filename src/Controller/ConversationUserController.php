<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Symfony\Component\Routing\Annotation\Route;
use App\FunctionU\MyFunction;
use App\Entity\User;
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
     * @Route("/message", name="newMessageUser", methods={"POST"})
     */
    public function newMessageUser(Request $request)
    {


        $data = [
            'emetteurId' => $request->get('emetteurId'),
            'message' => $request->get('message'),
            'receiverId' => $request->get('receiverId'),
        ];

        $requiredFields = [
            'emetteurId',
            'message',
            'receiverId'
        ];

        if (!$this->myFunction->checkRequiredFields($data, $requiredFields)) {
            return new CustomJsonResponse(null, 203, 'Vérifiez votre requête');
        }

        $emetteurId = $data['emetteurId'];
        $messageText = $data['message'];
        $receiverId = $data['receiverId'];

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

        return new CustomJsonResponse($messageSend, 201, 'Message envoyé avec succès');
    }
    /**
     * @Route("/conversations/{conversationId}", name="getMessageForConversation", methods={"GET"})
     */
    public function getMessageForConversation($conversationId): CustomJsonResponse
    {
        $conversation = $this->em->getRepository(ConversationUser::class)->find($conversationId);

        if (!$conversation) {
            return new CustomJsonResponse(null, 404, 'Conversation non trouvée');
        }

        $messages = $this->em->getRepository(MessageUser::class)->findBy(
            ['conversation' => $conversation],
            ['createdAt' => 'ASC']
        );

        $messagesData = [];
        foreach ($messages as $message) {
            $messagesData[] = $this->myFunction->formatMessageUser($message);
        }



        return new CustomJsonResponse($messagesData, 200, 'Messages récupérés avec succès');
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
     * @Route("/conversations/{userId}", name="getUserConversations", methods={"GET"})
     */
    public function getUserConversations($userId): CustomJsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($userId);

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
        foreach ($conversations as $conversation) {
            $otherUser = $conversation->getFirst()->getId() === $user->getId()
                ? $conversation->getSecond()
                : $conversation->getFirst();

            $conversationsData[] = [
                'id' => $conversation->getId(),
                'otherUser' => [
                    'id' => $otherUser->getId(),
                    'username' => $otherUser->getUsername()
                ],
                'lastMessage' => $this->getLastMessage($conversation)
            ];
        }

        return new CustomJsonResponse($conversationsData, 200, 'Conversations récupérées avec succès');
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
