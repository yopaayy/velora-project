"use client";

import { Link, usePathname } from "@/i18n/routing";
import { useTranslations } from "next-intl";
import { cn } from "@/lib/utils";
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  Users,
  Settings,
  Receipt,
  Truck,
  LineChart
} from "lucide-react";

interface SidebarProps extends React.HTMLAttributes<HTMLDivElement> {}

export function Sidebar({ className, ...props }: SidebarProps) {
  const pathname = usePathname();
  const t = useTranslations("Navigation");

  const sidebarNavItems = [
    { title: t("dashboard"), href: "/dashboard", icon: LayoutDashboard },
    { title: t("pos"), href: "/pos", icon: ShoppingCart },
    { title: t("sales"), href: "/sales", icon: Receipt },
    { title: t("inventory"), href: "/inventory", icon: Package },
    { title: t("purchasing"), href: "/purchasing", icon: Truck },
    { title: t("crm"), href: "/crm", icon: Users },
    { title: t("finance"), href: "/finance", icon: LineChart },
    { title: t("settings"), href: "/settings", icon: Settings },
  ];

  return (
    <div className={cn("pb-12 border-r h-screen bg-background", className)} {...props}>
      <div className="space-y-4 py-4">
        <div className="px-3 py-2">
          <h2 className="mb-6 px-4 text-2xl font-bold tracking-tight">
            Velora
          </h2>
          <div className="space-y-1">
            {sidebarNavItems.map((item, index) => {
              const isActive = pathname === item.href || pathname.startsWith(item.href + '/');
              return (
                <Link
                  key={index}
                  href={item.href}
                  className={cn(
                    "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground transition-all",
                    isActive ? "bg-accent text-accent-foreground" : "text-muted-foreground"
                  )}
                >
                  <item.icon className="h-4 w-4" />
                  {item.title}
                </Link>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
