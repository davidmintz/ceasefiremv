<?php

namespace App\Command;

use App\Entity\Rsvp;
use App\Enum\AttendanceMode;
use App\Repository\RsvpRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lists RSVPs for the configured meeting, so there is no need for an
 * admin web page (and the authentication it would require).
 *
 *   bin/console app:rsvp:list                 confirmed RSVPs + totals
 *   bin/console app:rsvp:list --all           include unconfirmed ones
 *   bin/console app:rsvp:list --zoom --emails addresses for the Zoom-link BCC
 */
#[AsCommand(
    name: 'app:rsvp:list',
    description: 'List RSVPs for the meeting configured in app.rsvp',
)]
class RsvpListCommand extends Command
{
    /** @param array{meeting: string, title: string, when: string} $rsvpConfig */
    public function __construct(
        private readonly RsvpRepository $repo,
        #[Autowire(param: 'app.rsvp')] private readonly array $rsvpConfig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Include RSVPs that have not been confirmed')
            ->addOption('zoom', null, InputOption::VALUE_NONE, 'Only people attending via Zoom')
            ->addOption('in-person', null, InputOption::VALUE_NONE, 'Only people attending in person')
            ->addOption('emails', null, InputOption::VALUE_NONE, 'Print only a comma-separated address list (for BCC)')
            ->addOption('meeting', 'm', InputOption::VALUE_REQUIRED, 'Meeting key (default: the one in app.rsvp)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('zoom') && $input->getOption('in-person')) {
            $io->error('Use --zoom or --in-person, not both.');

            return Command::INVALID;
        }

        $meeting = $input->getOption('meeting') ?? $this->rsvpConfig['meeting'];
        $mode = match (true) {
            (bool) $input->getOption('zoom') => AttendanceMode::Zoom,
            (bool) $input->getOption('in-person') => AttendanceMode::InPerson,
            default => null,
        };

        $rsvps = array_values(array_filter(
            $this->repo->findBy(['meeting' => $meeting], ['createdAt' => 'ASC']),
            fn (Rsvp $r) => ($input->getOption('all') || $r->isVerified())
                && (null === $mode || $r->getMode() === $mode),
        ));

        if ($input->getOption('emails')) {
            $output->writeln(implode(', ', array_map(
                fn (Rsvp $r) => '' === $r->getName()
                    ? $r->getEmail()
                    : sprintf('"%s" <%s>', str_replace('"', '', $r->getName()), $r->getEmail()),
                $rsvps,
            )));

            return Command::SUCCESS;
        }

        $io->newLine();
        $io->writeln(sprintf('  <options=bold>%s</> · %s', $this->rsvpConfig['title'], $this->rsvpConfig['when']));
        $io->writeln(sprintf('  <fg=gray>meeting key %s%s</>', $meeting, $input->getOption('all') ? ' · including unconfirmed' : ''));
        $io->newLine();

        if (!$rsvps) {
            $io->writeln('  No RSVPs'.($input->getOption('all') ? '.' : ' confirmed yet. <fg=gray>(Try --all to include unconfirmed.)</>'));
            $io->newLine();

            return Command::SUCCESS;
        }

        $tz = new \DateTimeZone(date_default_timezone_get());
        $fmt = fn (?\DateTimeImmutable $d) => $d?->setTimezone($tz)->format('D M j, g:i a');

        $rows = [];
        foreach ($rsvps as $i => $r) {
            $color = AttendanceMode::Zoom === $r->getMode() ? 'cyan' : 'green';
            $row = [
                $i + 1,
                $r->getName(),
                $r->getEmail(),
                sprintf('<fg=%s>%s</>', $color, $r->getMode()->label()),
                $fmt($r->getVerifiedAt()) ?? '<fg=yellow>not confirmed</>',
            ];
            if (!$r->isVerified()) { // dim the plain-text cells of unconfirmed rows
                foreach ([1, 2] as $c) {
                    $row[$c] = '<fg=gray>'.$row[$c].'</>';
                }
            }
            $rows[] = $row;
        }

        $count = fn (AttendanceMode $m, bool $verified) => count(array_filter(
            $rsvps,
            fn (Rsvp $r) => $r->getMode() === $m && $r->isVerified() === $verified,
        ));
        $summary = function (bool $verified) use ($count): string {
            $parts = [];
            $total = 0;
            foreach (AttendanceMode::cases() as $m) {
                $n = $count($m, $verified);
                $total += $n;
                $parts[] = sprintf('%s: <options=bold>%d</>', $m->label(), $n);
            }

            return sprintf('%s · total <options=bold>%d</>', implode(' · ', $parts), $total);
        };

        $table = (new Table($output))
            ->setStyle('box')
            ->setHeaders(['#', 'Name', 'Email', 'Attending', 'Confirmed'])
            ->setColumnStyle(0, (new TableStyle())->setPadType(\STR_PAD_LEFT))
            ->setRows($rows);
        $table->render();

        $io->newLine();
        $io->writeln('  Confirmed    '.$summary(true));
        if ($input->getOption('all')) {
            $io->writeln('  <fg=gray>Unconfirmed</>  '.$summary(false));
        }
        $io->newLine();

        return Command::SUCCESS;
    }
}
