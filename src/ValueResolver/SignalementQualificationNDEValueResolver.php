<?php

namespace App\ValueResolver;

use App\Dto\SignalementQualificationNDE;
use App\Entity\Enum\Qualification;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SignalementQualificationNDEValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (
            !$argumentType
            || SignalementQualificationNDE::class !== $argumentType
        ) {
            return [];
        }

        $dataDateEntree = $request->get('signalement-edit-nde-date-entree');
        $dataSuperficie = $request->get('signalement-edit-nde-superficie');

        $dataDernierBail = $request->get('signalement-edit-nde-dernier-bail');

        $dataConsoEnergie = $request->get('signalement-edit-nde-conso-energie');
        $dataDpe = 'null' === $request->get('signalement-edit-nde-dpe') ? null : (int) $request->get('signalement-edit-nde-dpe');
        $dataDpeDate = $request->get('signalement-edit-nde-dpe-date');

        $dto = new SignalementQualificationNDE(
            Qualification::NON_DECENCE_ENERGETIQUE->name,
            $dataDateEntree,
            new DateTimeImmutable($dataDernierBail),
            new DateTimeImmutable($dataDpeDate),
            $dataSuperficie,
            $dataConsoEnergie,
            $dataDpe
        );

        return [$dto];
    }
}
