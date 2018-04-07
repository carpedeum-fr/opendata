<?php

namespace App\Command;


use App\Entity\Address;
use App\Entity\Parish;
use App\Entity\Place;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Formatter\StringFormatter;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        /** @var Parish $parish */
        foreach ($parishes as $parish) {
            $egliseList = $this->client->request('GET', 'http://www.messes.info/api/v2/lieux-par-communaute/' . $parish->code . '?userkey=test&format=json');

            foreach (json_decode($egliseList->getBody(), true) as $eglise) {
                $place = new Place();
                $location = '';
                if (array_key_exists('name', $eglise)) {
                    $place->name = $eglise['name'];
                }
                if (array_key_exists('type', $eglise)) {
                    $place->type = $eglise['type'];
                }
                if (array_key_exists('picture', $eglise)) {
                    $place->picture = $eglise['picture'];
                }
                if (array_key_exists('address', $eglise)) {
                    $place->streetAddress = $eglise['address'];
                    $location .= $eglise['address'] . ' ';
                } else {
                    $location .= 'eglise ';
                }
                if (array_key_exists('zipcode', $eglise)) {
                    $place->postalCode = $eglise['zipcode'];
                    $location .= $eglise['zipcode'] . ' ';
                }
                if (array_key_exists('city', $eglise)) {
                    $place->addressLocality = $eglise['city'];
                    $location .= $eglise['city'];
                }
                if (array_key_exists('latitude', $eglise)) {
                    $place->latitude = $eglise['latitude'];
                }
                if (array_key_exists('longitude', $eglise)) {
                    $place->longitude = $eglise['longitude'];
                }

                $em->persist($place);

                // Query Google Maps API for a nice address
                $location .= ' France';

                try {
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
                }
                
                $em->flush();
            }
        }

    }
}