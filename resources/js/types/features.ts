/** Mirrors App\Enums\Module; tests/Feature/Enums/FrontendEnumMirrorTest.php keeps it in sync. */
export type ModuleName = 'chat' | 'documentation' | 'backup' | 'api';

export type ModuleFeature = {
    available: boolean;
    enabled: boolean;
};

export type Features = Record<ModuleName, ModuleFeature> & {
    realtime: boolean;
};
