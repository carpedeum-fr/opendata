<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Diocese;
use App\Entity\Parish;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Formatter\StringFormatter;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class MesseInfoImportParishCommand extends ImportCommand
{
    protected function configure()
    {
        $this
            ->setName('import:messeinfo:parish')
            ->setDescription('Import data using MesseInfo API.')
            ->addOption('diocese', null, InputOption::VALUE_REQUIRED,
                'Name of the diocese parish you want to import.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->stopwatch->start('parishImport');

        /** @var Diocese $diocese */
        foreach ($this->getDioceses($input->getOption('diocese')) as $diocese)
        {
            $io->note($diocese->name);
            $this->stopwatch->start($diocese->name);
            $paroisses = $this->getParish($diocese->code);
            $io->progressStart(count($paroisses));

            foreach ($paroisses as $paroisse) {
                if (!array_key_exists('alias', $paroisse)) {
                    $output->write('!');
                    continue;
                }

                $dbResult = $this->parishRepository->findOneByAlias($paroisse['alias']);
                if ($dbResult) {
                    $output->write('.');
                    continue;
                }

                $output->write('+');
                $this->prepareParish($diocese, $paroisse);
                $io->progressAdvance();
            }
            $this->timers[$diocese->name] = $this->stopwatch->stop($diocese->name);
            $io->progressFinish();
        }

        $event = $this->stopwatch->stop('parishImport');
        $io->note('Total duration: '.$event->getDuration());


        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($this->timers as $diocese => $timer) {
            $cleanTimers[] = [$diocese, $timer->getDuration().'ms'];
        }
        $io->table(
            array('Diocese', 'Duration'),
            $cleanTimers
        );
    }

    private function getDioceses($diocese)
    {
        if ($diocese) {
            $dioceses = $this->dioceseRepository->findByName($diocese);
        } else {
            $dioceses = $this->dioceseRepository->findAll();
        }

        return $dioceses;
    }

    private function preparePhone($paroisse, $diocese, $parish)
    {
        try {
            $phoneNumber = $this->phoneUtil->parse($paroisse['phone'], $diocese->country);
        } catch (NumberParseException $e) {
            $output->write('n');
        }
        if (isset($phoneNumber) && $this->phoneUtil->isValidNumber($phoneNumber)) {
            $parish->phone = $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            $parish->phoneNational = $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
            $parish->phoneInternational = $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        }
        $parish->phoneOriginal = $paroisse['phone'];
    }

    private function prepareParish($diocese, $paroisse)
    {
        $parish = new Parish();
        $parish->diocese = $diocese;
        $parish->code = $paroisse['id'];

        if (array_key_exists('responsible', $paroisse)) {
            $parish->responsible = trim(ucwords(strtolower($paroisse['responsible'])));
        }

        if (array_key_exists('description', $paroisse)) {
            $parish->description = trim(strip_tags(htmlspecialchars_decode($paroisse['description'])));
        }
        if (array_key_exists('phone', $paroisse)) {
            $this->preparePhone($paroisse, $diocese, $parish);
        }

        $parameters = ['alias', 'name', 'email', 'url', 'picture', 'type', 'communityType'];
        foreach ($parameters as $parameter) {
            if (array_key_exists($parameter, $paroisse)) {
                $parish->$parameter = trim($paroisse[$parameter]);
            }
        }

        $address = $paroisse['address'];
        $parameters = [
            'streetAddress' => 'street',
            'postalCode' => 'zipCode',
            'addressLocality' => 'city',
            'addressCountry' => 'region',
        ];
        foreach ($parameters as $key => $parameter) {
            if (array_key_exists($parameter, $address)) {
                $parish->$key = trim($address[$parameter]);
            }
        }

        if (in_array('latLng', $address)) {
            $parish->latitude = $address['latLng']['latitude'];
            $parish->longitude = $address['latLng']['longitude'];
            $parish->zoom = $address['latLng']['zoom'];
        }

        $this->em->persist($parish);
        $this->em->flush();
    }

    private function queryGoogleMaps($location, $parish)
    {
        // Query Google Maps API for a nice address
        // This should be done elsewhere to not slowdown the import.
        try {
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
            $this->em->persist($cleanedAddress);
        } catch (CollectionIsEmpty $e) {
            //$output->writeln('Nothing found for: '.$location);
        }
    }
}
