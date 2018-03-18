<?php

namespace App\Command;

use App\Entity\Diocese;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MesseInfoImportDioceseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:messeinfo')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new \GuzzleHttp\Client();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $dioceseList = $client->request('GET', 'https://www.messes.info/api/v2/dioceses/?format=json&userkey=test');
        $dioceses = [];

        foreach (json_decode($dioceseList->getBody(), true) as $messeInfoDiocese)
        {
            if (2 !== strlen($messeInfoDiocese['id'])) {
                $output->writeln('Skipping '.$messeInfoDiocese['name'].' because id is like: '.$messeInfoDiocese['id']);
                continue;
            }

            $dbResult = $em->getRepository(Diocese::class)->findOneByCode($messeInfoDiocese['id']);
            if ($dbResult) {
                $output->writeln('Skipping '.$dbResult->name.' because it\'s already saved in db.');
                continue;
            }

            $diocese = new Diocese();
            $diocese->code = $messeInfoDiocese['id'];
            $diocese->name = $messeInfoDiocese['name'];
            $diocese->url = $messeInfoDiocese['website'];

            if (array_key_exists('sector', $messeInfoDiocese)) {

                $googleApiQuery = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
                if ('Dom' === $messeInfoDiocese['sector'] || 'Tom' === $messeInfoDiocese['sector']) {
                    $googleApiQuery .= $messeInfoDiocese['name'];
                } else {
                    $googleApiQuery .= $messeInfoDiocese['sector'] . ' France';
                }
                $googleApiQuery .= '&key=AIzaSyDgSXYy-o41Q9I5cjPRYdLgO-JCSQpwsDw&language=fr';

                $cleanedGeoData = $client->request('GET', $googleApiQuery);
                $cleanedGeoDataJson = json_decode($cleanedGeoData->getBody(), true);
                foreach ($cleanedGeoDataJson['results'][0]['address_components'] as $component) {
                    if (in_array('country', $component['types'])) {
                        $diocese->country = $component['short_name'];
                    }
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        $diocese->region = $component['long_name'];
                    }
                }
                $diocese->latitude = $cleanedGeoDataJson['results'][0]['geometry']['location']['lat'];
                $diocese->longitude = $cleanedGeoDataJson['results'][0]['geometry']['location']['lng'];
            }

            $em->persist($diocese);
        }

        $em->flush();
    }
}