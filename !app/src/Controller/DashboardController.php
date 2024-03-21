<?php

namespace App\Controller;

use App\Entity\Email;
use App\Repository\EmailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{

    private $emailRepository;
    private $kernel;

    public function __construct(EmailRepository $emailRepository, KernelInterface $kernel)
    {
        $this->emailRepository = $emailRepository;
        $this->kernel = $kernel;
    }

    /**
     * @Route("/admin", name="dashboard")
     */
    public function index(Request $request): Response
    {
        $sortBy = $request->query->get('sortBy', 'id');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        $emails = $this->emailRepository->findBy([], [$sortBy => $sortOrder]);

        return $this->render('dashboard/index.html.twig', [
            'emails' => $emails,
        ]);
    }

    /**
     * @Route("/admin/import", name="dashboard_import")
     */
    public function retrieveEmails(Request $request): Response
    {
        $appName = $this->kernel->getEnvironment();

        $application = new Application($appName);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:retrieve-emails',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        return new Response($content);
    }
}
