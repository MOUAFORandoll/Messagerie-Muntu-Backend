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
use App\Entity\Groupe;
use App\Entity\GroupeUser;
use App\Entity\TypeObject;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Response\CustomJsonResponse;

class ConversationGroupeController extends AbstractController
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
     * @Route("/groupe/message", name="newGroupeMessage", methods={"POST"})
     */
    public function newGroupeMessage(Request $request)
    {
        $data = [
            'emetteurId' => $request->get('emetteurId'),
            'message' => $request->get('message'),
            'groupeId' => $request->get('groupeId'),
        ];

        $requiredFields = [
            'emetteurId',
            'message',
            'groupeId'
        ];

        if (!$this->myFunction->checkRequiredFields($data, $requiredFields)) {
            return new CustomJsonResponse([], 203, 'Vérifiez votre requête');
        }

        $emetteurId = $data['emetteurId'];
        $messageText = $data['message'];
        $groupeId = $data['groupeId'];

        $sender = $this->em->getRepository(User::class)->find($emetteurId);
        if (!$sender) {
            return new CustomJsonResponse([], 404, 'Émetteur non trouvé');
        }

        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);
        if (!$groupe) {
            return new CustomJsonResponse([], 404, 'Groupe non trouvé');
        }

        $groupeUser = $this->em->getRepository(GroupeUser::class)->findOneBy([
            'muntu' => $sender,
            'groupe' => $groupe
        ]);

        if (!$groupeUser) {
            return new CustomJsonResponse([], 403, 'Vous n\'êtes pas membre de ce groupe');
        }

        $message = new Message();
        $message->setValeur(rtrim($messageText));
        $message->setEmetteurGroupe($groupeUser);
        $message->setStatus(0);

        $this->em->persist($message);

        if (isset($data['typeObject']) && $data['typeObject'] != null) {
            $typeObjectId = $data['typeObject'];
            $typeObject = $this->em->getRepository(TypeObject::class)->find($typeObjectId);
            $fichiers = $this->createMessageObject($message, $request, $typeObject);
        }

        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageGroupe($message);

        return new CustomJsonResponse(
            [
                'message' => $messageSend
            ],
            201,
            'Message créé avec succès'
        );
    }
    /**
     * @Route("/groupe/message/status/{id}", name="updateGroupeMessageStatus", methods={"PATCH"})
     */
    public function updateMessageStatus(Request $request, $id): CustomJsonResponse
    {
        $message = $this->em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse([], 404, 'Message non trouvé');
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!in_array($newStatus, [0, 1, 2])) {
            return new CustomJsonResponse([], 203, 'Statut invalide. Les valeurs autorisées sont 0 (envoyé), 1 (reçu) ou 2 (lu).');
        }

        $message->setStatus($newStatus);
        $message->setUpdatedAt();
        $this->em->flush();
        $messageSend = $this->myFunction->formatMessageGroupe($message);
        return new CustomJsonResponse([
            'message' => $messageSend,
            'newStatus' => $newStatus
        ], 200, 'Statut du message mis à jour');
    }

    /**
     * @Route("/groupe/{groupeId}/messages", name="getGroupeMessages", methods={"GET"})
     */
    public function getGroupeMessages($groupeId): CustomJsonResponse
    {
        $groupe = $this->em->getRepository(Groupe::class)->find($groupeId);

        if (!$groupe) {
            return new CustomJsonResponse([], 404, 'Groupe non trouvé');
        }

        $messages = $this->em->getRepository(Message::class)->findBy(
            ['emetteurGroupe' => $groupe->getGroupeUsers()->toArray()],
            ['createdAt' => 'ASC']
        );

        $messagesData = [];
        foreach ($messages as $message) {
            $messagesData[] = $this->myFunction->formatMessageGroupe($message);
        }

        return new CustomJsonResponse([
            'messages' => $messagesData
        ], 200, 'Messages récupérés avec succès');
    }

    /**
     * @Route("/groupe/message/{id}", name="deleteGroupeMessage", methods={"DELETE"})
     */
    public function deleteGroupeMessage($id)
    {
        $message = $this->em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse([], 404, 'Message non trouvé');
        }

        $message->setDeletedAt();
        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageGroupe($message);

        return new CustomJsonResponse([
            'message' => $messageSend
        ], 200, 'Message supprimé avec succès');
    }

    /**
     * @Route("/groupe/message/{id}", name="updateGroupeMessage", methods={"PATCH"})
     */
    public function updateGroupeMessage(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['message'])) {
            return new CustomJsonResponse([], 203, 'Veuillez renseigner le message');
        }

        $message = $this->em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new CustomJsonResponse([], 404, 'Message non trouvé');
        }

        $message->setValeur($data['message']);
        $message->setUpdatedAt();
        $this->em->flush();

        $messageSend = $this->myFunction->formatMessageGroupe($message);

        return new CustomJsonResponse([
            'message' => $messageSend
        ], 200, 'Message mis à jour avec succès');
    }


    /**
     * @Route("/groupes/{userId}", name="getUserGroupes", methods={"GET"})
     */
    public function getUserGroupes($userId): CustomJsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($userId);

        if (!$user) {
            return new CustomJsonResponse([], 404, 'Utilisateur non trouvé');
        }

        $groupeUsers = $this->em->getRepository(GroupeUser::class)->findBy([
            'muntu' => $user
        ]);

        $groupesData = [];
        foreach ($groupeUsers as $groupeUser) {
            $groupe = $groupeUser->getGroupe();
            $groupesData[] = [
                'id' => $groupe->getId(),
                'libelle' => $groupe->getLibelle(),
                'description' => $groupe->getDescription(),
                'lastMessage' => $this->getLastMessage($groupe)
            ];
        }

        return new CustomJsonResponse([
            'groupes' => $groupesData
        ], 200, 'Groupes récupérés avec succès');
    }

    private function getLastMessage(Groupe $groupe): ?array
    {
        $lastMessage = $this->em->getRepository(Message::class)->findOneBy(
            ['emetteurGroupe' => $groupe->getGroupeUsers()->toArray()],
            ['createdAt' => 'DESC']
        );

        if (!$lastMessage) {
            return null;
        }

        return [
            'id' => $lastMessage->getId(),
            'content' => $lastMessage->getValeur(),
            'createdAt' => $lastMessage->getCreatedAt()->format('Y-m-d H:i:s'),
            'senderId' => $lastMessage->getEmetteurGroupe()->getMuntu()->getId()
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
                        $this->getParameter('groupe_files'),
                        $newFilenameData
                    );

                    $fichierObject = new MessageObject();
                    $fichierObject->setSrc($newFilenameData);
                    $fichierObject->setMessage($message);
                    $fichierObject->setTypeObject($typeObject);
                    $this->em->persist($fichierObject);

                    $fichiers[] = [
                        'src' => $_ENV['BACK_END_URL'] . '/images/groupe_files/' . $newFilenameData,
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
