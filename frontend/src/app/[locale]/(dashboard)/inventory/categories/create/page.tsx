"use client";

import { useState } from "react";
import { useRouter } from "@/i18n/routing";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { inventoryApi } from "@/lib/api/inventory";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { ArrowLeft } from "lucide-react";
import { Link } from "@/i18n/routing";

const categorySchema = z.object({
  name: z.string().min(2, { message: "Nama kategori minimal 2 karakter" }),
});

type CategoryFormValues = z.infer<typeof categorySchema>;

export default function CreateCategoryPage() {
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categorySchema),
    defaultValues: {
      name: "",
    },
  });

  async function onSubmit(data: CategoryFormValues) {
    setIsLoading(true);
    setError(null);
    try {
      await inventoryApi.createCategory({
        name: data.name,
      });
      router.push("/inventory");
      router.refresh();
    } catch (err: any) {
      setError(err.response?.data?.message || "Gagal membuat kategori");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="flex-1 space-y-4 max-w-2xl mx-auto">
      <div className="flex items-center space-x-2 mb-6">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/inventory">
            <ArrowLeft className="h-5 w-5" />
          </Link>
        </Button>
        <h2 className="text-2xl font-bold tracking-tight">Tambah Kategori</h2>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Informasi Kategori</CardTitle>
          <CardDescription>
            Kategori membantu Anda mengelompokkan produk agar mudah dicari.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nama Kategori</Label>
              <Input
                id="name"
                placeholder="Cth: Minuman Dingin"
                {...form.register("name")}
              />
              {form.formState.errors.name && (
                <p className="text-sm text-destructive">{form.formState.errors.name.message}</p>
              )}
            </div>

            {error && <p className="text-sm text-destructive font-medium">{error}</p>}

            <div className="flex justify-end pt-4">
              <Button type="submit" disabled={isLoading}>
                {isLoading ? "Menyimpan..." : "Simpan Kategori"}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
