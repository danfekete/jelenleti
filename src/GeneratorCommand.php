<?php
/**
 * Copyright (c) 2016, VOOV LLC.
 * All rights reserved.
 * Written by Daniel Fekete
 * daniel.fekete@voov.hu
 */

namespace danfekete\jelenleti;


use Jenssegers\Date\Date;
use League\Period\Period;
use PHPExcel_IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeneratorCommand extends Command
{
    protected function configure()
    {
        $this->setName('generate')
            ->setDescription('Generáljunk jelenléti ívet')
            ->addOption('month', 'm', InputOption::VALUE_REQUIRED)
            ->addOption('year', 'y', InputOption::VALUE_OPTIONAL, '', date('Y'));
            ;
    }

    private function convertPeriods($periodString, $year, $month)
    {
        if(empty($periodString) || $periodString == '0') return [];
        $clean = preg_replace("/[^0-9-,]/", '', $periodString);
        $dates = [];
        $elems = explode(',', $clean);

        foreach ($elems as $elem) {
            $parts = explode('-', $elem);

            if (count($parts) > 1) {
                foreach(range($parts[0], $parts[1]) as $day) {
                    $dates[] = Date::createFromDate($year, $month, $day);
                }
            } else {
                $dates[] =  Date::createFromDate($year, $month, $elem);
            }
        }

        return $dates;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Jelenléti ív generátor');
        Date::setLocale('hu');

        $excel = new \PHPExcel();

        $year = $input->getOption('year');
        $month = $input->getOption('month');
        $output->writeln($month);
        $start = Date::createFromDate($year, $month, 1);
        $end = clone $start;
        $end->endOfMonth();
        $period = new Period($start, $end);

        $names = file('nevek.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        $names = array_flip($names);
        foreach ($names as $name => &$params) {
            $szabadsag = $io->ask("{$name} szabadság", '0');
            $betegseg = $io->ask("{$name} betegség", '0');

            $params = [];
            $params["szabadsag"] = $this->convertPeriods($szabadsag, $year, $month);
            $params["betegseg"] = $this->convertPeriods($betegseg, $year, $month);
        }
        $rows = [];
        foreach ($period->getDatePeriod('1 DAY') as $day) {

            /** @var Date $d */
            /** @var \DateTimeImmutable $day */
            $d = Date::createFromFormat('Y-m-d', $day->format('Y-m-d'));
            $date = $d->format('F d.');
            $dayName = $d->format('l');
            $col = [$date, $dayName];
            foreach ($names as $name => $param) {
                $col[] = '08:00';
                $col[] = '16:00';
                $col[] = '8';
            }

            $rows[] = $col;
        }

        $sheet = $excel->getActiveSheet();
        $sheet->fromArray($rows, null, 'A4');

        $sheet->mergeCells('A2:B2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A2', $year);

        $sheet->fromArray(['Dátum', 'Nap'], null, 'A3');

        $nameList = array_keys($names);
        foreach ($nameList as $idx => $name) {
            $sheet->setCellValueByColumnAndRow((3*$idx) + 2, 2, $name);

            $sheet->setCellValueByColumnAndRow((3*$idx) + 2, 3, 'Kezdés');
            $sheet->setCellValueByColumnAndRow((3*$idx) + 3, 3, 'Végzés');
            $sheet->setCellValueByColumnAndRow((3*$idx) + 4, 3, 'Óra');

            //$sheet->mergeCellsByColumnAndRow((3*$idx) + 2, 2, (3*($idx+1)) + 1, 1);
            $sheet->getStyleByColumnAndRow((3*$idx) + 2, 2)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        $objWriter = PHPExcel_IOFactory::createWriter($excel, "Excel2007");
        $objWriter->save(sprintf("jelenleti_%d_%02d.xlsx", $year, $month));
    }
}