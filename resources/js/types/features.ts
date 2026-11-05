export type ModuleFeature = {
    available: boolean;
    enabled: boolean;
};

export type Features = {
    chat: ModuleFeature;
    documentation: ModuleFeature;
    backup: ModuleFeature;
    api: ModuleFeature;
    realtime: boolean;
};
