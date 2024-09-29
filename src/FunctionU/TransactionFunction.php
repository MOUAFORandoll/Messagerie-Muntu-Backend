<?php

namespace App\FunctionU;

use App\Entity\AbonnementBoutique;
use App\Entity\Agregateur;
use App\Entity\BonusPromos;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Compte;
use App\Entity\TransactionCompte;
use App\Entity\TransactionLivraison;
use App\Entity\TypeTransaction;
use App\Entity\UserPlateform;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Swift_Mailer;
use Swift_SmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Token;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionFunction
{

    private $em;
    private $client;

    public function __construct(
        EntityManagerInterface $em,
        HttpClientInterface $client,


    ) {
        $this->client =
            $client;
        $this->em = $em;
    }



    public function buyLivraison($data, $modepaiement)
    {


        if ($modepaiement == 1) {

            return $this->depotNotchPay($data);
        } else  if ($modepaiement == 2) {

            return $this->paiementCompte($data);
        } else {
        }
    }

    //  Paiement 

    public function paiementCompte($data)
    {

        $user = $this->em->getRepository(UserPlateform::class)->findOneBy(['keySecret' => $data['keySecret']]);

        if (!$user) {

            return    [
                'message' => "Compte introuvable, contacter un administrateur",

                'status' => false,

            ];
        };
        $compte = $this->em->getRepository(Compte::class)->findOneBy(['user' =>  $user]);
        if (!$compte) {

            return    [
                'message' => "Compte introuvable, contacter un administrateur",

                'status' => false,

            ];
        };
        if ($compte->getSolde() <= $data['amount']) {
            return   [

                'message' => "Solde insuffisant",


                'status' => false,

            ];
        };

        $this->newBonus($user);
        $compte->setSolde(
            $compte->getSolde() - $data['amount']
        );


        $this->em->persist($compte);





        $this->em->flush();

        return
            [

                'reference' =>
                $data['reference'],

                'status' => true,

            ];
    }


    //  Paiement 

    public function depotNotchPay($data)
    {

        //ici il s'agit de la cle publique de production nocth pay
        try {
            // return
            //     [
            //         $PAIEMENT_KEY,   $data
            //     ];
            $response = $this->client->request(
                'POST',
                'https://api.notchpay.co/payments/initialize',
                [

                    'headers' => ['Accept' => 'application/json', 'Authorization' =>      $_ENV['PAIEMENT_KEY']],
                    "json" => $data
                ]
            );

            $statusCodeInit = $response->getStatusCode();
            if ($statusCodeInit == 201) {
                if ($response->toArray()['code'] == 201) {


                    return
                        [
                            'token' => $response->toArray()["transaction"]['reference'],

                            'reference' => $response->toArray()["transaction"]['reference'],
                            'url' =>  $response->toArray()['authorization_url'],

                            'status' => true,

                        ];
                } else {

                    return    [

                        'status' => false,

                    ];
                }
            } else {
                return   [

                    'status' => false,

                ];
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function retraitNotchPay($data)
    {
        $amount     = $data['amount'];
        $phone     = $data['phone'];
        $service     =  $this->detectOperatorNotch($data['phone']);
        $reference     = $data['description'];
        //ici il s'agit de la cle publique de production nocth pay
        $PAIEMENT_KEY     =
            '';
        try {
            $data = [
                'amount' =>
                $amount,
                'beneficiary' => ["phone" => $phone],
                'channel' => $service,
                'currency' => 'XAF',
                'reference' => $reference,
                'country' => 'CM'
            ];
            $response = $this->client->request(
                'POST',
                'https://api.notchpay.co/transfers',
                [

                    'headers' => ['Accept' => 'application/json', 'Authorization' => $PAIEMENT_KEY],
                    "json" => $data
                ]
            );

            $statusCodeInit = $response->getStatusCode();
            if ($statusCodeInit == 201) {
                if ($response->toArray()['code'] == 201) {


                    return
                        [

                            'status' => true,

                        ];
                } else {

                    [

                        'status' => false,

                    ];
                }
            } else {
                [

                    'status' => false,

                ];
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function createTransactionLivraison($data,    $token)
    {
        $tyPeT
            = $this->em->getRepository(TypeTransaction::class)->findOneBy(['id' => 1]);

        $transaction = new TransactionLivraison();
        $transaction->setMotif($data['motif']);
        $transaction->setMontant($data['montant']);
        $transaction->setNumero($data['phone'] ?? '');

        $transaction->setStatus(0);

        $transaction->setTypeTransaction($tyPeT);


        $transaction->setToken($token);

        $this->em->persist($transaction);
        $this->em->flush();
        return
            $transaction;
    }
    public function createTransactionRetrait($data,   $token)
    {
        $tyPeT
            = $this->em->getRepository(TypeTransaction::class)->findOneBy(['id' => 2]);

        $transaction = new TransactionCompte();
        $transaction->setLibelle($data['motif']);
        $transaction->setMontant($data['montant']);
        $transaction->setNumeroClient($data['phone'] ?? '');

        $transaction->setStatus(false);

        $transaction->setTypeTransaction($tyPeT);

        $transaction->setToken($token);

        $this->em->persist($transaction);
        $this->em->flush();
        return true;
    }
    public function createTransactionDepotCompte($data,    $token)
    {
        $tyPeT
            = $this->em->getRepository(TypeTransaction::class)->findOneBy(['id' => 1]);

        $transaction = new TransactionCompte();
        $transaction->setLibelle($data['libelle']);
        $transaction->setMontant($data['montant']);
        $transaction->setNomClient($data['nom'] ?? '');
        $transaction->setPrenomClient($data['prenom'] ?? '');
        $transaction->setNumeroClient($data['numeroClient'] ?? '');

        $transaction->setStatus(false);
        $transaction->setClient($data['client']);
        $transaction->setTypeTransaction($data['typeTransaction']);


        $transaction->setTypeTransaction($tyPeT);


        $transaction->setToken($token);

        $this->em->persist($transaction);
        $this->em->flush();
        return
            $transaction;
    }
    public function paid($data,  $montant, $idCom)
    {
        $reponse = [];


        try {
            Stripe::setApiKey('sk_test_51MprWdFGCxqI1QzHZR3w2uP5G7oLhl58hXt4MDHqCUjywE1bdCP5YC4aqr0VVHilCTYmY7qohQfH4SyzvMD6bqKP00mxclsFcy');

            $token = Token::create([
                'card' => [
                    'number' => '4242424242424242',
                    'exp_month' => 03,
                    'exp_year' => 2026,
                    'cvc' => '868',
                ],
            ]);
            $charge = Charge::create([
                'amount' => 1000,
                // montant en centimes
                'currency' => 'eur',
                'source' => $token,
                // token de carte de crédit généré par Stripe.js
                'description' => 'Achat de produits',
            ]);

            if ($charge['status'] = 'success') {
                $reponse = true;
            }
            return $reponse;
        } catch (Exception $e) {
            $reponse = 0;
        }
    }


    public function verifyBuy($reference)
    {

        $response = $this->client->request(
            'GET',
            'https://api.notchpay.co/payments/' . $reference . '?currency=xaf',
            [

                'headers' => ['Content-Type' => 'application/json', 'Authorization' =>  $_ENV['PAIEMENT_KEY']],

            ]
        );
        $statusCodeInit = $response->getStatusCode();

        if ($statusCodeInit == 200) {
            if ($response->toArray()['code'] == 200) {


                return
                    $response->toArray()["transaction"]['status'];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
    function detectOperatorNotch($phoneNumber)
    {
        // Supprime les espaces et caractères non numériques
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Vérifie les préfixes
        if (preg_match('/^(650|651|652|653|654|67)/', $phoneNumber)) {
            return 'cm.mtn';
        } elseif (preg_match('/^(655|656|657|658|659|69)/', $phoneNumber)) {
            return 'cm.orange';
        } else {
            return 'cm.orange';
        }
    }


    public function reference()
    {

        $chaine = 'paiement';
        $listeCar = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = mb_strlen($listeCar, '8bit') - 1;
        for ($i = 0; $i < 31; ++$i) {
            $chaine .= $listeCar[random_int(0, $max)];
        }
        return $chaine;
    }

    /**Gestion des bonus  */

    public function newBonus($user)
    {


        if (!$user) {

            return    [

                'status' => false,

            ];
        };
        $bonus = new BonusPromos();

        $bonus->setClient($user);
        $bonus->setSolde(10);

        $this->em->persist($bonus);
        $this->em->flush();
        return
            [


                'status' => true,

            ];
    }

    public function getTotalbonusUser($user)
    {

        $soldeBonus = 0;
        if (!$user) {

            return    [

                'status' => false,

            ];
        };
        $listBonus
            = $this->em->getRepository(BonusPromos::class)->findBy(['client' => $user]);

        foreach ($listBonus as $bonus) {
            $soldeBonus += $bonus->getSolde();
        }
        return
            $soldeBonus;
    }
}
