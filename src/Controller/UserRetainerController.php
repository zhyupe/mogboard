<?php

namespace App\Controller;

use App\Entity\UserRetainer;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UserRetainerController extends AbstractController
{
    /** @var UserRetainers */
    private $retainers;
    
    public function __construct(UserRetainers $retainers)
    {
        $this->retainers = $retainers;
    }

    /**
     * Add a users character to the site, finding the ID should be
     * done via JS and XIVAPI search before it hits this endpoint
     *
     * submit:
     * - name, server
     *
     * @Route("/retainers/add", name="retainers_add")
     */
    public function add(Request $request)
    {
        return $this->json(
            $this->retainers->add($request)
        );
    }

    /**
     * @Route("/retainers/{retainer}/confirm", name="retainers_confirm")
     */
    public function confirm(UserRetainer $retainer)
    {
        return $this->json(
            $this->retainers->confirm($retainer)
        );
    }
    
    /**
     * @Route("/retainers/{slug}", name="retainer_store")
     */
    public function store(string $slug)
    {
        $retainer = $this->retainers->getSlugRetainer($slug);
        
        if ($retainer === null) {
            throw new NotFoundHttpException("Could not find a retainer for that url.");
        }
        
        return $this->render('UserRetainers/index.html.twig', [
            'retainer' => $retainer
        ]);
    }
}
