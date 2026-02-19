<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector;
use Rector\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\ConsistentImplodeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\Php71\Rector\List_\ListToArrayDestructRector;
use Rector\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Rector\Php80\Rector\ClassConstFetch\ClassOnThisVariableObjectRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/app'])
    ->withPhpSets(php83: true)
    ->withRules([
        ReplaceMultipleBooleanNotRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        ChangeArrayPushToArrayAssignRector::class,
        CompactToVariablesRector::class,
        SimplifyArraySearchRector::class,
        SimplifyConditionsRector::class,
        StrlenZeroToIdenticalEmptyStringRector::class,
        CommonNotEqualRector::class,
        LogicalToBooleanRector::class,
        SimplifyTautologyTernaryRector::class,
        SwitchNegatedTernaryRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        RemoveUselessParamTagRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,
        RemoveUselessVarTagRector::class,
        ChangeNestedIfsToEarlyReturnRector::class,
        RemoveAlwaysElseRector::class,
        ConsistentImplodeRector::class,
        ListToArrayDestructRector::class,
        NullCoalescingOperatorRector::class,
        ClassOnThisVariableObjectRector::class,
        ClassOnObjectRector::class,
        ChangeSwitchToMatchRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
        ReturnNeverTypeRector::class,
        DeclareStrictTypesRector::class,
    ]);
