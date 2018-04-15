<?php

namespace App\Command;


use App\Entity\Parish;
use App\Entity\Place;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class MesseInfoImportChurchCommand extends ImportCommand
{
    protected function configure()
    {
        $this
            ->setName('import:messeinfo:church')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->stopwatch->start('import');
        $parishes = $this->parishRepository->findAll();

        /** @var Parish $parish */
        foreach ($parishes as $parish) {
            $io->note($parish->name);
            $this->stopwatch->start($parish->name);

            $egliseArray = $this->getChurch($parish->code);
            $io->progressStart(count($egliseArray));

            foreach ($egliseArray as $eglise) {
                $dbResult = $this->churchRepository->findOneByMesseInfoId($eglise['id']);
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

                $this->em->persist($place);
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
            $this->em->flush();
            $this->timers[$parish->name] = $this->stopwatch->stop($parish->name);
            $io->progressFinish();
        }
        $event = $this->stopwatch->stop('import');
        $io->note('Total duration: '.$event->getDuration().'ms');

        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($this->timers as $parish => $timer) {
            $cleanTimers[] = [$parish, $timer->getDuration().'ms'];
        }
        $io->table(
            array('Parish', 'Duration'),
            $cleanTimers
        );
    }
}
