<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamArrayDocblockBasedOnCallableNativeFuncCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanConstReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromBooleanStrictReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromIterableMethodCallRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromObjectRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeFromArgRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddParamTypeForFunctionLikeWithinCallLikeArgDeclarationRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddParamTypeSplFixedArrayRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddReturnTypeDeclarationFromYieldsRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;

return static function (RectorConfig $config): void {
    // 📂 Adapt paths à ton projet si besoin
    $config->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $config->importNames();
    $config->parallel();
    $config->rules([
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddParamTypeSplFixedArrayRector::class,
        AddParamTypeForFunctionLikeWithinCallLikeArgDeclarationRector::class,
        AddClosureParamTypeFromIterableMethodCallRector::class,
        AddClosureParamTypeFromArgRector::class,
        ReturnTypeFromStrictTypedCallRector::class,
        BoolReturnTypeFromBooleanConstReturnsRector::class,
        BoolReturnTypeFromBooleanStrictReturnsRector::class,
        AddReturnTypeDeclarationRector::class,
        AddPropertyTypeDeclarationRector::class,
        AddClosureParamTypeFromObjectRector::class,
        AddReturnTypeDeclarationFromYieldsRector::class,
        AddMethodCallBasedStrictParamTypeRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        AddParamTypeBasedOnPHPUnitDataProviderRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        AddTestsVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        AddParamTypeDeclarationRector::class,
        AddParamTypeFromPropertyTypeRector::class,
        AddParamArrayDocblockBasedOnCallableNativeFuncCallRector::class,
    ]);

    $config->sets([
        // LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,
        // SetList::PHP_83,
        SetList::TYPE_DECLARATION,
    ]);

    $config->skip([
        __DIR__.'/var',
        __DIR__.'/vendor',
    ]);
};
