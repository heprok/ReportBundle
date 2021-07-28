<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Tlc\ManualBundle\Repository\PeopleRepository;
use Tlc\ReportBundle\Entity\BaseEntity;

abstract class AbstractReportController extends AbstractController
{
    protected \DatePeriod $period;
    protected array $sqlWhere = [];
    protected array $peoples = [];

    public function __construct(PeopleRepository $peopleRepository)
    {
        $request = Request::createFromGlobals();
        $idsPeople = (array)$request->query->get('people');

        $this->sqlWhere = json_decode($request->query->get('sqlWhere') ?? '[]');
        $this->period = BaseEntity::getPeriodFromArray((array)$request->query->get('period'));
        $this->type = !$idsPeople ? 'period' : 'shift';

        foreach ($idsPeople as $idPeople) {
            if ($idPeople != '')
                $this->peoples[] = $peopleRepository->find($idPeople);
        }
    }

    abstract public function showReportPdf();
    
}
