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
use App\Entity\Message;
use App\Entity\MessageObject;
use App\Entity\Canal;
use App\Entity\CanalUser;
use App\Entity\TypeObject;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ConversationCanalController extends AbstractController
{
    private $em;
    private $serializer;
    private $clientWeb;
    private $myFunction;

    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        HttpClientInterface $clientWeb,
        MyFunction $myFunction
    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->myFunction = $myFunction;
        $this->clientWeb = $clientWeb;
    }

    /**
     * @Route("/canal/message/status/{id}", name="updateCanalMessageStatus", methods={"PATCH"})
     */
    public function updateMessageStatus(Request $request, $id): JsonResponse
    {
        $message = $this->em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new JsonResponse([
                'message' => 'Message non trouvé'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!in_array($newStatus, [0, 1, 2])) {
            return new JsonResponse([
                'message' => 'Statut invalide. Les valeurs autorisées sont 0 (envoyé), 1 (reçu) ou 2 (lu).'
            ], 400);
        }

        $message->setStatus($newStatus);
        $message->setUpdatedAt();
        $this->em->flush();
        $messageSend = $this->myFunction->formatMessageCanal($message);
        return new JsonResponse([
            'message' => $messageSend,
            'newStatus' => $newStatus
        ], 200);
    }

    /**
     * @Route("/canal/message", name="newCanalMessage", methods={"POST"})
     */
    public function newCanalMessage(Request $request)
    {
        $data = [
            'emetteurId' => $request->get('emetteurId'),
            'message' => $request->get('message'),
            'canalId' => $request->get('canalId'),
        ];

        $requiredFields = [
            'emetteurId',
            'message',
            'canalId'
        ];

        if (!$this->myFunction->checkRequiredFields($data, $requiredFields)) {
            return new JsonResponse(['message' => 'Vérifiez votre requête'], 400);
        }

        $emetteurId = $data['emetteurId'];
        $messageText = $data['message'];
        $canalId = $data['canalId'];

        $sender = $this->em->getRepository(User::class)->find($emetteurId);
        if (!$sender) {
            return new JsonResponse([
                'message' => 'Émetteur non trouvé'
            ], 404);
        }

        $canal = $this->em->getRepository(Canal::class)->find($canalId);
        if (!$canal) {
            return new JsonResponse([
                'message' => 'Canal non trouvé'
            ], 404);
        }

        $canalUser = $this->em->getRepository(CanalUser::class)->findOneBy([
            'muntu' => $sender,
            'canal' => $canal
        ]);

        if (!$canalUser || $canalUser->getTypeParticipant()->getId() != 1) {
            return new JsonResponse([
                'message' => 'Vous n\'êtes pas autorisé à envoyer des messages dans ce canal'
            ], 403);
        }

        $message = new Message();
        $message->setValeur(rtrim($messageText));
        $message->setEmetteurCanal($canalUser);
        $message->setStatus(0);

        $this->em->persist($message);

        if (isset($data['typeObject']) && $data['typeObject'] != null) {
            $typeObjectId = $data['typeObject'];
            $typeObject = $this->em->getRepository(TypeObject::class)->find($typeObjectId);
            $fichiers = $this->createMessageObject($message, $request, $typeObject);
        }

        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageCanal($message);

        return new JsonResponse(
            [
                'message' => $messageSend
            ],
            201
        );
    }

    /**
     * @Route("/canal/{canalId}/messages", name="getCanalMessages", methods={"GET"})
     */
    public function getCanalMessages($canalId): JsonResponse
    {
        $canal = $this->em->getRepository(Canal::class)->find($canalId);

        if (!$canal) {
            return new JsonResponse([
                'message' => 'Canal non trouvé'
            ], 404);
        }

        $messages = $this->em->getRepository(Message::class)->findBy(
            ['emetteurCanal' => $canal->getCanalUsers()->toArray()],
            ['createdAt' => 'ASC']
        );

        $messagesData = [];
        foreach ($messages as $message) {
            $messagesData[] = $this->myFunction->formatMessageCanal($message);
        }

        return new JsonResponse([
            'messages' => $messagesData
        ], 200);
    }

    /**
     * @Route("/canal/message/{id}", name="deleteCanalMessage", methods={"DELETE"})
     */
    public function deleteCanalMessage($id)
    {
        $message = $this->em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new JsonResponse([
                'message' => 'Message non trouvé'
            ], 404);
        }

        $message->setDeletedAt();
        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageCanal($message);

        return new JsonResponse([
            'message' => $messageSend
        ], 200);
    }

    /**
     * @Route("/canal/message/{id}", name="updateCanalMessage", methods={"PATCH"})
     */
    public function updateCanalMessage(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['message'])) {
            return new JsonResponse([
                'message' => 'Veuillez renseigner le message'
            ], 400);
        }

        $message = $this->em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new JsonResponse([
                'message' => 'Message non trouvé'
            ], 404);
        }

        $message->setValeur($data['message']);
        $message->setUpdatedAt();
        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageCanal($message);

        return new JsonResponse([
            'message' => $messageSend
        ], 200);
    }


    /**
     * @Route("/canals/{userId}", name="getUserCanals", methods={"GET"})
     */
    public function getUserCanals($userId): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($userId);

        if (!$user) {
            return new JsonResponse([
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $canalUsers = $this->em->getRepository(CanalUser::class)->findBy([
            'muntu' => $user
        ]);

        $canalsData = [];
        foreach ($canalUsers as $canalUser) {
            $canal = $canalUser->getCanal();
            $canalsData[] = [
                'id' => $canal->getId(),
                'libelle' => $canal->getLibelle(),
                'description' => $canal->getDescription(),
                'lastMessage' => $this->getLastMessage($canal)
            ];
        }

        return new JsonResponse([
            'canals' => $canalsData
        ], 200);
    }

    private function getLastMessage(Canal $canal): ?array
    {
        $lastMessage = $this->em->getRepository(Message::class)->findOneBy(
            ['emetteurCanal' => $canal->getCanalUsers()->toArray()],
            ['createdAt' => 'DESC']
        );

        if (!$lastMessage) {
            return null;
        }

        return [
            'id' => $lastMessage->getId(),
            'content' => $lastMessage->getValeur(),
            'createdAt' => $lastMessage->getCreatedAt()->format('Y-m-d H:i:s'),
            'senderId' => $lastMessage->getEmetteurCanal()->getMuntu()->getId()
        ];
    }

    private function createMessageObject(Message $message, Request $file_send, TypeObject $typeObject)
    {
        $fichiers = [];
        for ($i = 0; $i < count($file_send->files); $i++) {
            $file = $file_send->files->get('file' . $i);

            if ($file) {
                $newFilenameData = $this->myFunction->getUniqueFileNameMessage($file->guessExtension()) . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('call_center'),
                        $newFilenameData
                    );

                    $fichierObject = new MessageObject();
                    $fichierObject->setSrc($newFilenameData);
                    $fichierObject->setMessage($message);
                    $fichierObject->setTypeObject($typeObject);
                    $this->em->persist($fichierObject);

                    $fichiers[] = [
                        'src' => $_ENV['BACK_END_URL'] . '/images/call_center/' . $newFilenameData,
                    ];
                } catch (FileException $e) {
                    // Gérer l'exception
                    break;
                }
            }
        }

        return $fichiers;
    }
}
