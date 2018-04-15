<?php


namespace App\Command;


use App\Entity\Place;
use App\Entity\Time;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class MesseInfoImportTimetableCommand extends ImportCommand
{
    protected function configure()
    {
        $this
            ->setName('import:messeinfo:time')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $places = $this->churchRepository->findAll();
        $this->stopwatch->start('timeImport');

        /** @var Place $place */
        foreach ($places as $place) {
            $io->note($place->name);
            $this->stopwatch->start($place->name);
            $timetables = $this->getTime($place->messeInfoId);
            $io->progressStart(count($timetables));

            foreach ($timetables as $horaire) {
                $time = $this->timeRepository->findOneByMesseInfoId($horaire['id']);
                if (!$time) {
                    $time = new Time();
                }

                $time->messeInfoId = $horaire['id'];
                $time->place = $place;
                $time->datetime = new \DateTime($horaire['date'] .'T'. str_replace('h', ':', $horaire['time']));
                if (array_key_exists('length', $horaire)) {
                    $time->length = $horaire['length'];
                }
                if (array_key_exists('tags', $horaire)) {
                    $time->tags = implode(', ', $horaire['tags']);
                }
                if (array_key_exists('active', $horaire)) {
                    $time->isActive = $horaire['active'];
                }
                if (array_key_exists('valid', $horaire)) {
                    $time->isValid = $horaire['valid'];
                }
                if (array_key_exists('updateDate', $horaire)) {
                    $time->setUpdatedAt(new \DateTime($horaire['updateDate']));
                }
                if (array_key_exists('celebrationTimeType', $horaire)) {
                    $time->celebrationType = $horaire['celebrationTimeType'];
                }
                if (array_key_exists('timeType', $horaire)) {
                    $time->timeType = $horaire['timeType'];
                }
                if (array_key_exists('recurrenceCategory', $horaire)) {
                    $time->recurrenceCategory = $horaire['recurrenceCategory'];
                }

                $this->em->persist($time);
                $io->progressAdvance();

            }
            $this->em->flush();
            $this->timers[$place->name] = $this->stopwatch->stop($place->name);
            $io->progressFinish();
        }
        $event = $this->stopwatch->stop('timeImport');
        $io->note('Total duration: ' . $event->getDuration() . 'ms');

        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($this->timers as $place => $timer) {
            $cleanTimers[] = [$place, $timer->getDuration() . 'ms'];
        }
        $io->table(
            array('Parish', 'Duration'),
            $cleanTimers
        );
    }
}