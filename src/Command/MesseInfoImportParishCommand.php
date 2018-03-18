<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Diocese;
use App\Entity\Parish;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Formatter\StringFormatter;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use GuzzleHttp\Client;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MesseInfoImportParishCommand extends ContainerAwareCommand
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
            ->setName('import:messeinfo:parish')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dioceses = $em->getRepository(Diocese::class)->findAll();
        $phoneUtil = PhoneNumberUtil::getInstance();

        /** @var Diocese $diocese */
        foreach ($dioceses as $diocese)
        {
            $output->writeln($diocese->name);
            $paroisseList = $this->client->request('GET', 'http://www.messes.info/api/v2/diocese/'.$diocese->code.'?userkey=test&format=json');
            foreach (json_decode($paroisseList->getBody(), true) as $paroisse){
                if (!array_key_exists('alias', $paroisse)) {
                    $output->write('!');
                    continue;
                }

                $dbResult = $em->getRepository(Parish::class)->findOneByAlias($paroisse['alias']);
                if ($dbResult) {
                    $output->write('.');
                    continue;
                }

                $output->write('+');
                $parish = new Parish();
                $parish->diocese = $diocese;
                $parish->code = $paroisse['id'];
                $parish->alias = $paroisse['alias'];
                $parish->name = $paroisse['name'];
                $parish->type = $paroisse['type'];
                if (array_key_exists('responsible', $paroisse)) {
                    $parish->responsible = ucwords($paroisse['responsible']);
                }
                if (array_key_exists('description', $paroisse)) {
                    $parish->description = strip_tags(htmlspecialchars_decode($paroisse['description']));
                }
                if (array_key_exists('email', $paroisse)) {
                    $parish->email = $paroisse['email'];
                }

                if (array_key_exists('phone', $paroisse)) {
                    try {
                        $phoneNumber = $phoneUtil->parse($paroisse['phone'], $diocese->country);
                    } catch (NumberParseException $e) {
                        $output->write('n');
                    }
                    if (isset($phoneNumber) && $phoneUtil->isValidNumber($phoneNumber)) {
                        $parish->phone = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
                        $parish->phoneNational = $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
                        $parish->phoneInternational = $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
                    }
                    $parish->phoneOriginal = $paroisse['phone'];
                }
                if (array_key_exists('url', $paroisse)) {
                    $parish->url = $paroisse['url'];
                }
                $parish->communityType = $paroisse['communityType'];
                if (array_key_exists('picture', $paroisse)) {
                    $parish->picture = $paroisse['picture'];
                }
                $em->persist($parish);

                $originalAddress = new Address();
                $originalAddress->parish = $parish;
                $originalAddress->name = 'Donnée importée de MesseInfo.';
                if (array_key_exists('street', $paroisse['address'])) {
                    $originalAddress->streetAddress = $paroisse['address']['street'];
                }
                if (array_key_exists('zipCode', $paroisse['address'])) {
                    $originalAddress->postalCode = $paroisse['address']['zipCode'];
                }
                if (array_key_exists('city', $paroisse['address'])) {
                    $originalAddress->addressLocality = $paroisse['address']['city'];
                }
                $originalAddress->addressCountry = $paroisse['address']['region'];
                if (in_array('latLng', $paroisse['address'])) {
                    $originalAddress->latitude = $paroisse['address']['latLng']['latitude'];
                    $originalAddress->longitude = $paroisse['address']['latLng']['longitude'];
                    $originalAddress->zoom = $paroisse['address']['latLng']['zoom'];
                }
                $em->persist($originalAddress);

                $location = '';
                if (array_key_exists('street', $paroisse['address'])) {
                    $location .= $paroisse['address']['street'] . ' ';
                } else {
                    $location .= 'eglise ';
                }
                if (array_key_exists('zipCode', $paroisse['address'])) {
                    $location .= $paroisse['address']['zipCode'] . ' ';
                }
                if (array_key_exists('city', $paroisse['address'])) {
                    $location .= $paroisse['address']['city'];
                }
                $location .= $paroisse['address']['region'];

                try {
                    $geoData = $this->provider->geocodeQuery(GeocodeQuery::create($location))->first();
                    $cleanedAddress = new Address();
                    $cleanedAddress->parish = $parish;
                    $cleanedAddress->name = 'Donnée de MesseInfo traitée par Google Maps.';
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
            }
            $em->flush();
        }
    }
}
