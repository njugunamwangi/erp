<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

   protected static ?string $slug = 'defaults';

   protected static ?string $navigationGroup = 'Settings';

   protected static ?string $navigationLabel = 'Defaults';

   protected static ?string $modelLabel = 'Defaults';
}
