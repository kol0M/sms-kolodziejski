<?php

namespace App\Command;

use App\Entity\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetrieveEmailsCommand extends Command
{
    protected static $defaultName = 'app:retrieve-emails';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Retrieve emails')
            ->setHelp('This command retrieves emails from the mail server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = '{imap.iq.pl:993/imap/ssl}INBOX'; // IMAP server and port
        $username = 'sms-rekrutacja@ironteam-raporty.pl'; // Email address
        $password = 'sLdVcoRBu23ltiRIrwVt'; // Password

        $imapStream = imap_open($server, $username, $password);

        if (!$imapStream) {
            $output->writeln('Failed to connect to the IMAP server.');
            return Command::FAILURE;
        }

        $emails = imap_search($imapStream, 'ALL');

        if (!$emails) {
            $output->writeln('No emails found.');
        } else {
            $output->writeln('Emails retrieved successfully:');
            foreach ($emails as $email) {
                $header = imap_fetchheader($imapStream, $email);

                preg_match('/Subject: (.+)\r\n/', $header, $matches);
                $subject = isset($matches[1]) ? $matches[1] : 'No Subject';

                if ($subject === 'Odbiór SMS') {

                    $structure = imap_fetchstructure($imapStream, $email);

                    $emailBody = '';

                    if ($structure) {
                        if (isset($structure->parts) && is_array($structure->parts)) {
                            foreach ($structure->parts as $partNumber => $part) {
                                $body = imap_fetchbody($imapStream, $email, $partNumber + 1);

                                if (isset($part->encoding) && $part->encoding == 4) {
                                    $body = quoted_printable_decode($body);
                                }

                                $emailBody .= $body;
                            }
                        } else {
                            // Fetch body
                            $emailBody = imap_body($imapStream, $email);
                        }

                        preg_match('/<strong>Nadawca:<\/strong>\s*(.*?)\s*<strong>/', $emailBody, $senderMatches);
                        $sender = isset($senderMatches[1]) ? trim(strip_tags($senderMatches[1])) : 'Unknown Sender';

                        preg_match('/<strong>Odbiorca:<\/strong>\s*(.*?)\s*<br>/', $emailBody, $recipientMatches);
                        $recipient = isset($recipientMatches[1]) ? trim(strip_tags($recipientMatches[1])) : 'Unknown Recipient';

                        preg_match('/<strong>Treść odebranej wiadomości:<\/strong>\s*(.*?)\s*<br>/', $emailBody, $contentMatches);
                        $content = isset($contentMatches[1]) ? trim(strip_tags($contentMatches[1])) : 'Unknown Content';

                        preg_match('/<strong>Data:<\/strong>\s*(.*?)\s*<br>/', $emailBody, $dateMatches);
                        $time = isset($dateMatches[1]) ? new \DateTime(trim($dateMatches[1])) : new \DateTime();
                    }

                    $existingEmail = $this->entityManager->getRepository(Email::class)->findOneBy([
                        'sender' => $sender,
                        'recipient' => $recipient,
                        'content' => $content,
                        'date' => $time,
                    ]);

                    if (!$existingEmail) {
                        $newEmail = new Email();
                        $newEmail->setSender($sender);
                        $newEmail->setRecipient($recipient);
                        $newEmail->setContent($content);
                        $newEmail->setDate($time);

                        $this->entityManager->persist($newEmail);
                        $output->writeln('Email saved successfully.');
                    } else {
                        $output->writeln('Email already exists, skipping.');
                    }
                }
            }

            $this->entityManager->flush();
            $output->writeln('All emails processed.');
        }

        imap_close($imapStream);

        return Command::SUCCESS;
    }
}
