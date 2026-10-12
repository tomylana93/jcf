export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

export type ManageTwoFactorProps = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};
