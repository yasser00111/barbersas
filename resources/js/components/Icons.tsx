import { SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

function Base({ children, ...props }: IconProps & { children: React.ReactNode }) {
    return (
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.7} {...props}>
            {children}
        </svg>
    );
}

export const HomeIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M3 10.5L12 3l9 7.5M5 9.5V21h14V9.5" />
    </Base>
);

export const QueueIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h10M4 18h7" />
    </Base>
);

export const UsersIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M17 20v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1M10 11a4 4 0 100-8 4 4 0 000 8zM21 20v-1a4 4 0 00-3-3.87M16 3.13A4 4 0 0119 7a4 4 0 01-3 3.87" />
    </Base>
);

export const ScissorsIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M6 3l12 12M6 21L18 9M9 6a3 3 0 11-4.24 4.24M18 15a3 3 0 11-4.24 4.24" />
    </Base>
);

export const ReportIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M8 17v-5m4 5V7m4 10v-3M5 3h14a1 1 0 011 1v16a1 1 0 01-1 1H5a1 1 0 01-1-1V4a1 1 0 011-1z" />
    </Base>
);

export const CardIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M3 8a3 3 0 013-3h12a3 3 0 013 3v8a3 3 0 01-3 3H6a3 3 0 01-3-3V8zm0 3h18" />
    </Base>
);

export const CogIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.2.5.7.9 1.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z" />
    </Base>
);

export const QrIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 3h2m2 0h2m-6 3h2m4-3v3" />
    </Base>
);

export const ShopIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 9l1-5h14l1 5M4 9v11h16V9M4 9h16M9 20v-6h6v6" />
    </Base>
);

export const InvoiceIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M6 2h9l5 5v15H6V2zm8 0v6h6M9 13h6M9 17h6" />
    </Base>
);

export const ShieldIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z" />
    </Base>
);

export const ClockIcon = (p: IconProps) => (
    <Base {...p}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </Base>
);
