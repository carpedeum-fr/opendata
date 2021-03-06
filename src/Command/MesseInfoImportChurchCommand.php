<?php

namespace App\Command;


use App\Entity\Parish;
use App\Entity\Place;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $this->stopwatch->start('global');
        $parishes = $this->parishRepository->findAll();
        $io->progressStart(count($parishes));

        /** @var Parish $parish */
        foreach ($parishes as $parish) {
            $this->stopwatch->start($parish->name);
            $eglises = $this->getChurch($parish->code);


            foreach ($eglises as $eglise) {
                $dbResult = $this->churchRepository->findOneByMesseInfoId($eglise['id']);
                if ($dbResult) {
                    continue;
                }

                $place = new Place();
                $place->parish = $parish;
                $place->messeInfoId = $eglise['id'];

                $parameters = [
                    'name' => 'name',
                    'type' => 'type',
                    'picture' => 'picture',
                    'streetAddress' => 'address',
                    'postalCode' => 'zipcode',
                    'addressLocality' => 'city',
                    'latitude' => 'latitude',
                    'longitude' => 'longitude'
                ];
                foreach ($parameters as $key => $parameter) {
                    if (array_key_exists($parameter, $eglise)) {
                        $place->$key = trim($eglise[$parameter]);
                    }
                }
                $this->em->persist($place);
            }
            $this->em->flush();
            $this->timers[$parish->name] = $this->stopwatch->stop($parish->name);
            $io->progressAdvance();

        }
        $io->progressFinish();

        $this->dumpRecap($io);
    }

    private function queryGoogleMaps()
    {
        // Query Google Maps API for a nice address
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
}
