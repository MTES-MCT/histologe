<?php

namespace App\Command\Cron;

use App\Entity\FailedEmail;
use App\Repository\FailedEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:retry-failed-emails',
    description: 'Retry sending failed emails',
)]
class RetryFailedEmailsCommand extends AbstractCronCommand
{
    /** @var string[] */
    public const array ERRORS_TO_IGNORE = [
        'Unable to send an email: email is not valid in to (code 400).',
        'An email must have a "To", "Cc", or "Bcc" header.',
    ];
    public const int START_AT_YEAR = 2025;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FailedEmailRepository $failedEmailRepository,
        private readonly MailerInterface $mailer,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var FailedEmail[] $failedEmails */
        $failedEmails = $this->failedEmailRepository->findEmailToResend();

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

            if (!$failedEmail->isRecipientVisible() && isset($failedEmail->getReplyTo()[0])) {
                $emailMessage->addTo($emailMessage->getReplyTo()[0]);
            }
            foreach ($failedEmail->getToEmail() as $toEmail) {
                if ($toEmail && 'NC' !== $toEmail) {
                    if ($failedEmail->isRecipientVisible()) {
                        $emailMessage->addTo($toEmail);
                    } else {
                        $emailMessage->addBcc($toEmail);
                    }
                }
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
                        $attachPath && $emailMessage->attachFromPath($attachPath);
                    }
                } else {
                    $attachPath = $failedEmail->getContext()['attach'];
                    $attachPath && $emailMessage->attachFromPath($attachPath);
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
