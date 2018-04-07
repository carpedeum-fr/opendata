<?php

namespace App\Command;

use App\Entity\Diocese;
use App\Entity\Parish;
use Geocoder\ProviderAggregator;
use GuzzleHttp\Client;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

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
        $stopwatch = new Stopwatch();
        $stopwatch->start('parishImport');
        $timers = [];

        /** @var Diocese $diocese */
        foreach ($dioceses as $diocese)
        {
            $io = new SymfonyStyle($input, $output);
            $io->note($diocese->name);

            $stopwatch->start($diocese->name);
            $paroisseList = $this->client->request('GET', 'http://www.messes.info/api/v2/diocese/'.$diocese->code.'?userkey=test&format=json');
            $paroisseArray = json_decode($paroisseList->getBody(), true);
            $io->progressStart(count($paroisseArray));

            foreach ($paroisseArray as $paroisse) {
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

                $location = '';
                if (array_key_exists('street', $paroisse['address'])) {
                    $parish->streetAddress = $paroisse['address']['street'];
                    $location .= $paroisse['address']['street'] . ' ';
                } else {
                    $location .= 'eglise ';
                }
                if (array_key_exists('zipCode', $paroisse['address'])) {
                    $parish->postalCode = $paroisse['address']['zipCode'];
                    $location .= $paroisse['address']['zipCode'] . ' ';
                }
                if (array_key_exists('city', $paroisse['address'])) {
                    $parish->addressLocality = $paroisse['address']['city'];
                    $location .= $paroisse['address']['city'];
                }
                $parish->addressCountry = $paroisse['address']['region'];
                if (in_array('latLng', $paroisse['address'])) {
                    $parish->latitude = $paroisse['address']['latLng']['latitude'];
                    $parish->longitude = $paroisse['address']['latLng']['longitude'];
                    $parish->zoom = $paroisse['address']['latLng']['zoom'];
                }
                $em->persist($parish);
                $em->flush();
                $io->progressAdvance();

                // Query Google Maps API for a nice address
                $location .= $paroisse['address']['region'];

                // This should be done elsewhere to not slowdown the import.
                /*try {
                    $geoData = $this->provider->geocodeQuery(GeocodeQuery::create($location))->first();
                    $cleanedAddress = new Address();
                    $cleanedAddress->parish = $parish;
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
            $timers[$diocese->name] = $stopwatch->stop($diocese->name);
            $io->progressFinish();
        }

        $event = $stopwatch->stop('parishImport');
        $io->note('Total duration: '.$event->getDuration());


        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($timers as $diocese => $timer) {
            $cleanTimers[] = [$diocese, $timer->getDuration().'ms'];
        }
        $io->table(
            array('Diocese', 'Duration'),
            $cleanTimers
        );
    }
}
