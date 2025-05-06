<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\FunctionLike\AddParamTypeSplFixedArrayRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromIterableMethodCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPHPDocRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeFromConstructorRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\PHPDoc\Rector\ClassMethod\AddParamTypeTagRector;
use Rector\PHPDoc\Rector\ClassMethod\AddReturnTagRector;
use Rector\TypeDeclaration\Rector\Param\AddParamTypeDeclarationRector;
// use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\PHPDoc\Rector\ClassMethod\AddParamTypeDocRector;
use Rector\PHPDoc\Rector\ClassMethod\AddReturnTypeDocRector;
use Rector\PHPDoc\Rector\Property\PropertyTypeHintToPHPDocRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\FunctionLike\ParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;
use Rector\Strict\Rector\FunctionLike\ParamTypeDeclarationFromStrictTypedCallRector;

return static function (RectorConfig $config): void {
    // 📂 Adapt paths à ton projet si besoin
    $config->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);



    $config->importNames();
    $config->parallel();

    // $config->autoloadPaths([
    //     __DIR__ . '/vendor/autoload.php',
    // ]);

    $config->rules([
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddParamTypeSplFixedArrayRector::class,
        AddClosureParamTypeFromIterableMethodCallRector::class,
        ReturnTypeFromStrictTypedCallRector::class,
        AddReturnTypeDeclarationRector::class,
        AddPropertyTypeDeclarationRector::class,
        // AddParamTypeDeclarationRector::class,
        // AddParamTypeFromPHPDocRector::class,
        // AddPropertyTypeFromConstructorRector::class,
        // AddParamTypeTagRector::class,
        // AddReturnTagRector::class,
        // AddParamTypeDocRector::class,
        // AddReturnTypeDocRector::class,
        // PropertyTypeHintToPHPDocRector::class,
        // ParamTypeFromStrictTypedCallRector::class,
        // ParamTypeDeclarationRector::class,
        // ReturnTypeDeclarationRector::class,
        // ParamTypeDeclarationFromStrictTypedCallRector::class,
    ]);


    $config->sets([
        // migration de php vers la version 8.3
        LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,      // petites améliorations de code
        SetList::PHP_83,
        // typer les retours des fonctions
        SetList::TYPE_DECLARATION,   
    ]);

    // Pour éviter les erreurs sur des classes manquantes
    $config->skip([
        __DIR__ . '/var',
        __DIR__ . '/vendor',
    ]);
};
