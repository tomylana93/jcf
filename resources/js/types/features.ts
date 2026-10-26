export type ModuleFeature = {
    available: boolean;
    enabled: boolean;
};

export type WebsiteFeature = ModuleFeature & {
    public: boolean;
};

export type Features = {
    website: WebsiteFeature;
    chat: ModuleFeature;
    documentation: ModuleFeature;
    backup: ModuleFeature;
    realtime: boolean;
};
