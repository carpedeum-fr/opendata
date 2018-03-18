<?php

namespace App\Command;

use App\Entity\Diocese;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MesseInfoImportDioceseCommand extends ContainerAwareCommand
{
    private $client;
    private $provider;

    public function __construct(ProviderAggregator $provider)
    {
        $this->client = new Client();
        $this->provider = $provider;

        // this is required due to parent constructor, which sets up name
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('import:messeinfo:diocese')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dioceseList = $this->client->request('GET', 'https://www.messes.info/api/v2/dioceses/?format=json&userkey=test');

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

                if ('Dom' === $messeInfoDiocese['sector'] || 'Tom' === $messeInfoDiocese['sector']) {
                    $location = $messeInfoDiocese['name'];
                } else {
                    $location = $messeInfoDiocese['sector'] . ' France';
                }

                $geoData = $this->provider->geocodeQuery(GeocodeQuery::create($location))->first();
                $diocese->country = $geoData->getCountry()->getCode();
                $diocese->region = $geoData->getAdminLevels()->first()->getName();
                $diocese->latitude = $geoData->getCoordinates()->getLatitude();
                $diocese->longitude = $geoData->getCoordinates()->getLongitude();
            }

            $em->persist($diocese);
        }

        $em->flush();
    }
}