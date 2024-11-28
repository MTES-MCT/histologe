<?php

namespace App\Command;

use App\Entity\FailedEmail;
use App\Repository\FailedEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:retry-failed-emails',
    description: 'Retry sending failed emails',
)]
class RetryFailedEmailsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FailedEmailRepository $failedEmailRepository,
        private MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var FailedEmail[] $failedEmails */
        $failedEmails = $this->failedEmailRepository->findBy(['isResendSuccessful' => false]);

        foreach ($failedEmails as $failedEmail) {
            $emailMessage = (new TemplatedEmail())
                ->htmlTemplate('emails/'.$failedEmail->getContext()['template'].'.html.twig')
                ->context($failedEmail->getContext())
                ->replyTo($failedEmail->getReplyTo())
                ->subject($failedEmail->getSubject())
                ->from(
                    new Address(
                        $failedEmail->getFromEmail(),
                        $failedEmail->getFromFullname()
                    )
                )
            ;

            foreach ($failedEmail->getToEmail() as $toEmail) {
                $toEmail && $emailMessage->addTo($toEmail);
            }
            if (
                \array_key_exists('tagHeader', $failedEmail->getContext())
                && null !== $failedEmail->getContext()['tagHeader']
            ) {
                $emailMessage->getHeaders()->add(new TagHeader($failedEmail->getContext()['tagHeader']));
            }
            if (\array_key_exists('attach', $failedEmail->getContext())) {
                if (\is_array($failedEmail->getContext()['attach'])) {
                    foreach ($failedEmail->getContext()['attach'] as $attachPath) {
                        $emailMessage->attachFromPath($attachPath);
                    }
                } else {
                    $emailMessage->attachFromPath($failedEmail->getContext()['attach']);
                }
            }
            if (\array_key_exists('attachContent', $failedEmail->getContext())) {
                $emailMessage->attach(
                    $failedEmail->getContext()['attachContent']['content'],
                    $failedEmail->getContext()['attachContent']['filename']
                );
            }

            try {
                $this->mailer->send($emailMessage);

                $io->success(sprintf('E-mail envoyé à %s', implode(', ', $failedEmail->getToEmail())));
                $failedEmail->setResendSuccessful(true);
            } catch (\Throwable $exception) {
                $io->error(sprintf('E-mail non envoyé à %s', implode(', ', $failedEmail->getToEmail())));
                $failedEmail->setRetryCount($failedEmail->getRetryCount() + 1);
                $failedEmail->setLastAttemptAt(new \DateTimeImmutable());
                $failedEmail->setErrorMessage($exception->getMessage());
            }
            $this->entityManager->flush();
        }

        $io->success('Traitement des e-mails échoués terminé.');

        return Command::SUCCESS;
    }
}
