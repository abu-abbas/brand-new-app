/// <reference types="vite/client" />

declare module '*?raw' {
  const content: string;
  export default content;
}

interface AppConfig {
  name: string;
  env: string;
  url: string;
  timezone: string;
  locale: string;
  current_date: string;
  current_fulldate: string;
}

interface Window {
  __APP_CONFIG__?: AppConfig;
}
