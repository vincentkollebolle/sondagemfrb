<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Partie;
use AppBundle\Entity\Rubrique;
use AppBundle\Entity\Proposition;
use AppBundle\Entity\Sondage;
use AppBundle\Entity\Reponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        
        $parties = $this->getDoctrine()
            ->getRepository(Partie::class)
            ->findAll();

        //récupérer les propositions
        $propositions = $this->getDoctrine()
            ->getRepository(Proposition::class)
            ->findAll();

        $sondage = new Sondage();
        $sondage->setOfficiel(false);
        
        $formBuilder = $this->createFormBuilder($sondage);
        $formBuilder->add('label', TextType::class, array('label' => 'Nom du RDB'));
        $formBuilder->add('email', TextType::class, array('label' => 'Votre E-mail'));
        $formBuilder->add('codepostal', TextType::class, array('label' => 'Votre Code postal'));
        foreach($propositions as $proposition) {
            $name2give = "prop".$proposition->getId();
            $formBuilder->add($name2give, CheckboxType::class, array(
                'label'    => $proposition->getLabel(),
                'mapped' => false,
                'required' => false,
                'data' => false
            ));
        }
        $formBuilder->add('save', SubmitType::class, array('label' => 'Publier'));
        $form = $formBuilder->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            //récupérer les sondages officiel
            $sondageofficiels = $this->getDoctrine()
            ->getRepository(Sondage::class)
            ->findByOfficiel(true);

            $arrayresult =  array();
            foreach($sondageofficiels as $sondageofficiel) {
                //créer le compteur
                $resultat["sondageNom"] = $sondageofficiel->getLabel();
                $resultat["sondageId"] = $sondageofficiel->getId();
                $resultat["resultat"] = 0;
                
                //pour chaque sondage récup toutes les réponses
                $reponses = $sondageofficiel->getReponses();
                foreach($reponses as $reponse) {
                    $id2look4 = "prop".$reponse->getProposition()->getId();
                    $val = $form->get($id2look4)->getData();
                    if($val == $reponse->getValue()) {
                        $resultat["resultat"] = $resultat["resultat"]+1;
                    }
                }
                $arrayresult[] = $resultat;
            }
            //parcourrir chaque sondage officiel
            //faire un compteur de reponse identique
            //. afficher les compteurs dans l'ordre.
            echo "<h1>Vos résultats : </h1>";
            foreach ($arrayresult as $value) {
                echo "Rdb: ".$value['sondageNom']." > score: ".$value['resultat']." / ".count($propositions);
                echo '<br />';
            }
            die();
        }    
        return $this->render('default/index.html.twig', array(
            'parties' => $parties,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/admin/parties", name="admin_parties")
     */
    public function adminpartiesAction(Request $request)
    {
        
        $partie = new Partie();
        $parties = $this->getDoctrine()
            ->getRepository(Partie::class)
            ->findAll();

        $form = $this->createFormBuilder($partie)
            ->add('label', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Ajouter'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $partie = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($partie);
            $entityManager->flush();

            return $this->redirectToRoute('admin_parties');
        }


        return $this->render('default/parties.html.twig', array(
            'form' => $form->createView(),
            'parties' => $parties
        ));
        
    }

    /**
     * @Route("/admin/rubriques", name="admin_rubriques")
     */
    public function adminrubriquesAction(Request $request)
    {
        
        $rubrique = new Rubrique();
        
        $rubriques = $this->getDoctrine()
            ->getRepository(Rubrique::class)
            ->findAll();

        $form = $this->createFormBuilder($rubrique)
            ->add('partie', EntityType::class, array(
                'class' => Partie::class,
                'choice_label' => 'label'
            ))
            ->add('label', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Ajouter'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rubrique = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($rubrique);
            $entityManager->flush();

            return $this->redirectToRoute('admin_rubriques');
        }


        return $this->render('default/rubriques.html.twig', array(
            'form' => $form->createView(),
            'rubriques' => $rubriques
        ));

    }

    /**
     * @Route("/admin/propositions", name="admin_propositions")
     */
    public function adminpropositionsAction(Request $request)
    {
        $proposition = new Proposition();
        
        $parties = $this->getDoctrine()
            ->getRepository(Partie::class)
            ->findAll();

        $form = $this->createFormBuilder($proposition)
            ->add('rubrique', EntityType::class, array(
                'class' => Rubrique::class,
                'choice_label' => 'label'
            ))
            ->add('label', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Ajouter'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proposition = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($proposition);
            $entityManager->flush();

            return $this->redirectToRoute('admin_propositions');
        }


        return $this->render('default/propositions.html.twig', array(
            'form' => $form->createView(),
            'parties' => $parties
        ));

    }


    
    /**
     * @Route("/admin/revenudebase", name="admin_revenudebase")
     */
    public function adminrevenudebaseAction(Request $request)
    {
        $sondage = new Sondage();
        $sondage->setOfficiel(true); 

        $sondages = $this->getDoctrine()
            ->getRepository(Sondage::class)
            ->findAll();

        //récupérer les propositions
        $propositions = $this->getDoctrine()
            ->getRepository(Proposition::class)
            ->findAll();

        $formBuilder = $this->createFormBuilder($sondage);
        $formBuilder->add('label', TextType::class, array('label' => 'Nom du RDB'));
        $formBuilder->add('email', TextType::class, array('label' => 'Votre E-mail'));
        $formBuilder->add('codepostal', TextType::class, array('label' => 'Votre Code postal'));
        foreach($propositions as $proposition) {
            $name2give = "prop".$proposition->getId();
            $formBuilder->add($name2give, CheckboxType::class, array(
                'label'    => $proposition->getLabel(),
                'mapped' => false,
                'required' => false,
                'data' => false
            ));
        }
        $formBuilder->add('save', SubmitType::class, array('label' => 'Publier'));
        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //foreach proposition get data and print-it
            foreach($propositions as $proposition) {
                $id2look4 = "prop".$proposition->getId();
                $val = $form->get($id2look4)->getData();
                if($val != false) {
                    //pour chaque valeur cochée on créer une reponse 
                    //et on l'ajout au sondage à persister.
                    $reponse2add = new Reponse();
                    $reponse2add->setSondage($sondage);
                    $reponse2add->setProposition($proposition);
                    $reponse2add->setValue(true);
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($reponse2add);

                    //ajout de la reponse au sondage
                    
                }
            }
            $entityManager->persist($sondage);
            $entityManager->flush();
            return $this->redirectToRoute('admin_revenudebase');
        }

        return $this->render('default/revenudebase.html.twig', array(
            'form' => $form->createView(),
            'sondages' => $sondages
        ));

    }
}
