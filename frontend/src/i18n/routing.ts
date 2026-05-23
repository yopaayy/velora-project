import {defineRouting} from 'next-intl/routing';
import {createNavigation} from 'next-intl/navigation';
 
export const routing = defineRouting({
  locales: ['en', 'id'],
  defaultLocale: 'id',
  localePrefix: 'as-needed' // Only prefix non-default locales, e.g. /en/dashboard vs /dashboard
});
 
// Lightweight wrappers around Next.js' navigation APIs
export const {Link, redirect, usePathname, useRouter, getPathname} =
  createNavigation(routing);
