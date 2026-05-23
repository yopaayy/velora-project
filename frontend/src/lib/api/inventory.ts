import { axiosInstance } from "../axios";

export interface Category {
  id: string;
  name: string;
  parent_id?: string;
  color?: string;
  icon?: string;
  is_active: boolean;
  sort_order: number;
}

export interface Product {
  id: string;
  name: string;
  cost_price: number;
  selling_price: number;
  stock_quantity: number;
  category_id?: string;
  sku?: string;
  barcode?: string;
  status: string;
  track_stock: boolean;
  category?: Category;
}

export const inventoryApi = {
  getCategories: async (): Promise<Category[]> => {
    const response = await axiosInstance.get("/pos/categories");
    return response.data.data;
  },

  createCategory: async (data: Partial<Category>): Promise<Category> => {
    const response = await axiosInstance.post("/pos/categories", data);
    return response.data.data;
  },

  getProducts: async (params?: any): Promise<{ data: Product[], total: number }> => {
    const response = await axiosInstance.get("/pos/products", { params });
    // Assuming Laravel standard pagination wrapper: response.data.data.data
    return {
      data: response.data.data.data,
      total: response.data.data.total
    };
  },

  createProduct: async (data: Partial<Product>): Promise<Product> => {
    const response = await axiosInstance.post("/pos/products", data);
    return response.data.data;
  }
};
