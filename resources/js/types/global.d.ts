declare module 'ziggy-js' {
  interface Ziggy {
    routes: Record<string, any>;
    url: string;
    port: number | null;
    defaults: Record<string, any>;
    location: string;
  }

  const Ziggy: Ziggy;
  export default Ziggy;
}

declare module '@inertiajs/core' {
  export const router: any;
}

declare global {
  interface Window {
    _: any;
    axios: any;
  }
}
