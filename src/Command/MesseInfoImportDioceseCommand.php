<?php

namespace App\Command;

use App\Entity\Diocese;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Query\GeocodeQuery;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MesseInfoImportDioceseCommand extends ImportCommand
{
    protected function configure()
    {
        $this
            ->setName('import:messeinfo:diocese')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getDiocese() as $messeInfoDiocese)
        {
            if (2 !== strlen($messeInfoDiocese['id'])) {
                $output->writeln('Skipping '.$messeInfoDiocese['name'].' because id is like: '.$messeInfoDiocese['id']);
                continue;
            }

            $dbResult = $this->dioceseRepository->findOneByCode($messeInfoDiocese['id']);
            if ($dbResult) {
                $output->writeln('Skipping '.$dbResult->name.' because it\'s already saved in db.');
                continue;
            }

            $diocese = new Diocese();
            $diocese->code = $messeInfoDiocese['id'];
            $diocese->name = $messeInfoDiocese['name'];
            $diocese->url = $messeInfoDiocese['website'];

            if (array_key_exists('sector', $messeInfoDiocese)) {

                if ('Dom' === $messeInfoDiocese['sector'] || 'Tom' === $messeInfoDiocese['sector']) {
                    $location = $messeInfoDiocese['name'];
                } else {
                    $location = $messeInfoDiocese['sector'] . ' France';
                }

                $geoData = $this->provider->geocodeQuery(GeocodeQuery::create($location))->first();
                $diocese->country = $geoData->getCountry()->getCode();
                try {
                    $diocese->region = $geoData->getAdminLevels()->first()->getName();
                } catch (CollectionIsEmpty $e) {
                    $diocese->region = '';
                }
                $diocese->latitude = $geoData->getCoordinates()->getLatitude();
                $diocese->longitude = $geoData->getCoordinates()->getLongitude();
            }

            $this->em->persist($diocese);
        }

        $this->em->flush();
    }
}