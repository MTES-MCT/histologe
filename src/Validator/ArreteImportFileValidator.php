<?php

namespace App\Validator;

use App\Service\Import\Arrete\ArreteImportHeader;
use App\Service\Import\Arrete\ArreteImportRow;
use App\Service\Import\CsvParser;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ArreteImportFileValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ArreteImportFile) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ArreteImportFile');
        }

        if (!$value instanceof UploadedFile) {
            return;
        }

        $csvParser = new CsvParser()
            ->setFirstLine(ArreteImportRow::FIRST_LINE)
            ->autoDetectDelimiter($value->getPathname());
        $headers = $csvParser->getHeaders($value->getPathname());
        $missingHeaders = array_diff(ArreteImportHeader::REQUIRED_HEADERS, $headers);

        if (!empty($missingHeaders)) {
            $this->context
                ->buildViolation($constraint->missingHeadersMessage)
                ->setParameter('{{ headers }}', implode(', ', $missingHeaders))
                ->addViolation();

            return;
        }

        $data = $csvParser->parseAsDict($value->getPathname());

        if (empty($data)) {
            $this->context
                ->buildViolation($constraint->emptyFileMessage)
                ->addViolation();
        }

        if (\count($data) > 50) {
            $this->context
                ->buildViolation($constraint->tooManyLinesMessage)
                ->addViolation();
        }

        $uniqueRows = [];
        $duplicateLines = [];
        $headerOffset = ((int) ($csvParser->getOptions()['first_line'] ?? 0)) + 1;
        foreach ($data as $index => $row) {
            $rowString = implode('|', $row);
            if (isset($uniqueRows[$rowString])) {
                $duplicateLines[] = $index + $headerOffset;
            }
            $uniqueRows[$rowString] = true;
        }

        if (!empty($duplicateLines)) {
            $this->context
                ->buildViolation($constraint->duplicateLinesMessage)
                ->setParameter('{{ lines }}', implode(', ', $duplicateLines))
                ->addViolation();
        }
    }
}
