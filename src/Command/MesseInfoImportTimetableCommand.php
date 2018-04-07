<?php


namespace App\Command;


use App\Entity\Place;
use App\Entity\Time;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class MesseInfoImportTimetableCommand extends ContainerAwareCommand
{
    private $client;

    public function __construct()
    {
        $this->client= new Client();

        // this is required due to parent constructor, which sets up name
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('import:messeinfo:time')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $places = $em->getRepository(Place::class)->findAll();
        $stopwatch = new Stopwatch();
        $stopwatch->start('timeImport');

        /** @var Place $place */
        foreach ($places as $place) {
            $io = new SymfonyStyle($input, $output);
            $io->note($place->name);
            $stopwatch->start($place->name);
            $timetableList = $this->client->request('GET', 'http://www.messes.info/api/v2/horaires_par_lieu/' . $place->messeInfoId . '?userkey=test&format=json');
            $timetableArray = json_decode($timetableList->getBody(), true);
            $io->progressStart(count($timetableArray));

            foreach ($timetableArray as $horaire) {
                $dbResult = $em->getRepository(Time::class)->findOneByMesseInfoId($horaire['id']);
                if ($dbResult) {
                    $io->progressAdvance();
                    continue;
                }

                $time = new Time();

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
                if (array_key_exists('updatedate', $horaire)) {
                    $time->setUpdatedAt(new \DateTime($horaire['updatedate']));
                }
                if (array_key_exists('celebrationtimetype', $horaire)) {
                    $time->celebrationType = $horaire['celebrationtimetype'];
                }
                if (array_key_exists('timetype', $horaire)) {
                    $time->timeType = $horaire['timetype'];
                }
                if (array_key_exists('recurrencecategory', $horaire)) {
                    $time->recurrenceCategory = $horaire['recurrencecategory'];
                }

                $em->persist($time);
                $io->progressAdvance();

            }
            $em->flush();
            $timers[$place->name] = $stopwatch->stop($place->name);
            $io->progressFinish();
        }
        $event = $stopwatch->stop('timeImport');
        $io->note('Total duration: ' . $event->getDuration() . 'ms');

        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($timers as $place => $timer) {
            $cleanTimers[] = [$place, $timer->getDuration() . 'ms'];
        }
        $io->table(
            array('Parish', 'Duration'),
            $cleanTimers
        );
    }
}