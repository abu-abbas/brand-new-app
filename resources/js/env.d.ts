/// <reference types="vite/client" />

declare module '*?raw' {
  const content: string;
  export default content;
}

interface AppReferenceOption {
  value: string;
  label: string;
}

interface AppConfig {
  name: string;
  env: string;
  url: string;
  timezone: string;
  locale: string;
  current_date: string;
  current_fulldate: string;
  captcha: {
    enabled: boolean;
  };
  references: {
    permission_types: AppReferenceOption[];
  };
}

interface Window {
  __APP_CONFIG__?: AppConfig;
}
