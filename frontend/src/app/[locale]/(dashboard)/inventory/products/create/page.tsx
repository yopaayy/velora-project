"use client";

import { useState, useEffect } from "react";
import { useRouter } from "@/i18n/routing";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { inventoryApi, Category } from "@/lib/api/inventory";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { ArrowLeft } from "lucide-react";
import { Link } from "@/i18n/routing";

const productSchema = z.object({
  name: z.string().min(2, { message: "Nama produk minimal 2 karakter" }),
  sku: z.string().optional(),
  category_id: z.string().optional(),
  cost_price: z.coerce.number().min(0, "Harga modal tidak boleh negatif"),
  selling_price: z.coerce.number().min(0, "Harga jual tidak boleh negatif"),
  stock_quantity: z.coerce.number().min(0, "Stok tidak boleh negatif"),
});

type ProductFormValues = z.infer<typeof productSchema>;

export default function CreateProductPage() {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Fetch categories for the dropdown
    inventoryApi.getCategories().then(setCategories).catch(console.error);
  }, []);

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      name: "",
      sku: "",
      category_id: "",
      cost_price: 0,
      selling_price: 0,
      stock_quantity: 0,
    },
  });

  async function onSubmit(data: ProductFormValues) {
    setIsLoading(true);
    setError(null);
    try {
      await inventoryApi.createProduct({
        ...data,
        category_id: data.category_id || undefined,
      });
      router.push("/inventory");
      router.refresh();
    } catch (err: any) {
      setError(err.response?.data?.message || "Gagal membuat produk");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="flex-1 space-y-4 max-w-3xl mx-auto pb-10">
      <div className="flex items-center space-x-2 mb-6">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/inventory">
            <ArrowLeft className="h-5 w-5" />
          </Link>
        </Button>
        <h2 className="text-2xl font-bold tracking-tight">Tambah Produk</h2>
      </div>

      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Informasi Dasar</CardTitle>
            <CardDescription>Detail utama dari produk Anda.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nama Produk *</Label>
              <Input id="name" placeholder="Cth: Kopi Susu Gula Aren" {...form.register("name")} />
              {form.formState.errors.name && <p className="text-sm text-destructive">{form.formState.errors.name.message}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="sku">SKU (Stock Keeping Unit)</Label>
                <Input id="sku" placeholder="Cth: KPG-001" {...form.register("sku")} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="category_id">Kategori</Label>
                <select 
                  id="category_id" 
                  className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  {...form.register("category_id")}
                >
                  <option value="">-- Pilih Kategori --</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Harga & Inventaris</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="cost_price">Harga Modal (Rp)</Label>
                <Input id="cost_price" type="number" {...form.register("cost_price")} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="selling_price">Harga Jual (Rp) *</Label>
                <Input id="selling_price" type="number" {...form.register("selling_price")} />
                {form.formState.errors.selling_price && <p className="text-sm text-destructive">{form.formState.errors.selling_price.message}</p>}
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="stock_quantity">Stok Awal</Label>
              <Input id="stock_quantity" type="number" {...form.register("stock_quantity")} />
            </div>
            
            {error && <p className="text-sm text-destructive font-medium">{error}</p>}
          </CardContent>
        </Card>

        <div className="flex justify-end">
          <Button type="submit" size="lg" disabled={isLoading}>
            {isLoading ? "Menyimpan..." : "Simpan Produk"}
          </Button>
        </div>
      </form>
    </div>
  );
}
