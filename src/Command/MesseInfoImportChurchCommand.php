<?php

namespace App\Command;


use App\Entity\Parish;
use App\Entity\Place;
use Geocoder\ProviderAggregator;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class MesseInfoImportChurchCommand extends ContainerAwareCommand
{
    private $client;
    private $provider;

    public function __construct(ProviderAggregator $provider)
    {
        $this->client= new Client();
        $this->provider = $provider;

        // this is required due to parent constructor, which sets up name
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('import:messeinfo:church')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $parishes = $em->getRepository(Parish::class)->findAll();
        $stopwatch = new Stopwatch();
        $stopwatch->start('churchImport');

        /** @var Parish $parish */
        foreach ($parishes as $parish) {
            $io = new SymfonyStyle($input, $output);
            $io->note($parish->name);
            $stopwatch->start($parish->name);
            $egliseList = $this->client->request('GET', 'http://www.messes.info/api/v2/lieux-par-communaute/' . $parish->code . '?userkey=test&format=json');
            $egliseArray = json_decode($egliseList->getBody(), true);
            $io->progressStart(count($egliseArray));

            foreach ($egliseArray as $eglise) {
                $dbResult = $em->getRepository(Place::class)->findOneByMesseInfoId($eglise['id']);
                if ($dbResult) {
                    $io->progressAdvance();
                    continue;
                }

                $place = new Place();
                $location = '';
                $place->messeInfoId = $eglise['id'];
                if (array_key_exists('name', $eglise)) {
                    $place->name = trim($eglise['name']);
                }
                if (array_key_exists('type', $eglise)) {
                    $place->type = $eglise['type'];
                }
                if (array_key_exists('picture', $eglise)) {
                    $place->picture = $eglise['picture'];
                }
                if (array_key_exists('address', $eglise)) {
                    $place->streetAddress = trim($eglise['address']);
                    $location .= $eglise['address'] . ' ';
                } else {
                    $location .= 'eglise ';
                }
                if (array_key_exists('zipcode', $eglise)) {
                    $place->postalCode = trim($eglise['zipcode']);
                    $location .= $eglise['zipcode'] . ' ';
                }
                if (array_key_exists('city', $eglise)) {
                    $place->addressLocality = trim($eglise['city']);
                    $location .= $eglise['city'];
                }
                if (array_key_exists('latitude', $eglise)) {
                    $place->latitude = $eglise['latitude'];
                }
                if (array_key_exists('longitude', $eglise)) {
                    $place->longitude = $eglise['longitude'];
                }

                $place->parish = $parish;

                $em->persist($place);
                $io->progressAdvance();

                // Query Google Maps API for a nice address
                $location .= ' France';

                // This should be done elsewhere to not slowdown the import.
                /*try {
                    $geoData = $this->provider->geocodeQuery(GeocodeQuery::create($location))->first();
                    $cleanedAddress = new Address();
                    $cleanedAddress->place = $place;
                    $cleanedAddress->origin = 'Google Maps';
                    if ($geoData->getCountry()) {
                        $cleanedAddress->addressCountry = $geoData->getCountry()->getCode();
                    }
                    $cleanedAddress->addressLocality = $geoData->getLocality();
                    $cleanedAddress->postalCode = $geoData->getPostalCode();
                    $cleanedAddress->streetAddress = $geoData->getStreetNumber().' '.$geoData->getStreetName();
                    $formatter = new StringFormatter();
                    $cleanedAddress->formattedAddress = $formatter->format($geoData, '%n, %S, %z %L, %C');
                    $cleanedAddress->longitude = $geoData->getCoordinates()->getLongitude();
                    $cleanedAddress->latitude = $geoData->getCoordinates()->getLatitude();
                    $em->persist($cleanedAddress);
                } catch (CollectionIsEmpty $e) {
                    //$output->writeln('Nothing found for: '.$location);
                }*/
            }
            $em->flush();
            $timers[$parish->name] = $stopwatch->stop($parish->name);
            $io->progressFinish();
        }
        $event = $stopwatch->stop('churchImport');
        $io->note('Total duration: '.$event->getDuration().'ms');

        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($timers as $parish => $timer) {
            $cleanTimers[] = [$parish, $timer->getDuration().'ms'];
        }
        $io->table(
            array('Parish', 'Duration'),
            $cleanTimers
        );
    }
}
