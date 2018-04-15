<?php

namespace App\Command;

use App\Entity\Diocese;
use App\Entity\Parish;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
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

    private function getDioceses($diocese)
    {
        if ($diocese) {
            $dioceses = $this->dioceseRepository->findByName($diocese);
        } else {
            $dioceses = $this->dioceseRepository->findAll();
        }

        return $dioceses;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $phoneUtil = PhoneNumberUtil::getInstance();
        $this->stopwatch->start('parishImport');

        /** @var Diocese $diocese */
        foreach ($this->getDioceses($input->getOption('diocese')) as $diocese)
        {
            $io->note($diocese->name);
            $this->stopwatch->start($diocese->name);
            $paroisseArray = $this->getParish($diocese->code);
            $io->progressStart(count($paroisseArray));

            foreach ($paroisseArray as $paroisse) {
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
                $parish = new Parish();
                $parish->diocese = $diocese;
                $parish->code = $paroisse['id'];
                $parish->alias = $paroisse['alias'];
                $parish->name = trim($paroisse['name']);
                $parish->type = $paroisse['type'];
                if (array_key_exists('responsible', $paroisse)) {
                    $parish->responsible = trim(ucwords(strtolower($paroisse['responsible'])));
                }
                if (array_key_exists('description', $paroisse)) {
                    $parish->description = trim(strip_tags(htmlspecialchars_decode($paroisse['description'])));
                }
                if (array_key_exists('email', $paroisse)) {
                    $parish->email = trim($paroisse['email']);
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
                    $parish->url = trim($paroisse['url']);
                }
                $parish->communityType = $paroisse['communityType'];
                if (array_key_exists('picture', $paroisse)) {
                    $parish->picture = trim($paroisse['picture']);
                }

                $location = '';
                if (array_key_exists('street', $paroisse['address'])) {
                    $parish->streetAddress = trim($paroisse['address']['street']);
                    $location .= $paroisse['address']['street'] . ' ';
                } else {
                    $location .= 'eglise ';
                }
                if (array_key_exists('zipCode', $paroisse['address'])) {
                    $parish->postalCode = trim($paroisse['address']['zipCode']);
                    $location .= $paroisse['address']['zipCode'] . ' ';
                }
                if (array_key_exists('city', $paroisse['address'])) {
                    $parish->addressLocality = trim($paroisse['address']['city']);
                    $location .= $paroisse['address']['city'];
                }
                $parish->addressCountry = $paroisse['address']['region'];
                if (in_array('latLng', $paroisse['address'])) {
                    $parish->latitude = $paroisse['address']['latLng']['latitude'];
                    $parish->longitude = $paroisse['address']['latLng']['longitude'];
                    $parish->zoom = $paroisse['address']['latLng']['zoom'];
                }
                $this->em->persist($parish);
                $this->em->flush();
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
}
