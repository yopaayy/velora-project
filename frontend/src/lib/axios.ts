import axios from "axios";

export const axiosInstance = axios.create({
  baseURL: "http://localhost:8000/api/v1",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Interceptor untuk menambahkan Bearer Token dan Tenant ID
axiosInstance.interceptors.request.use((config) => {
  if (typeof window !== "undefined") {
    const token = localStorage.getItem("auth_token");
    const businessId = localStorage.getItem("business_id");
    const branchId = localStorage.getItem("branch_id");

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    if (businessId) {
      config.headers["X-Business-Id"] = businessId;
    }
    if (branchId) {
      config.headers["X-Branch-Id"] = branchId;
    }
  }
  return config;
});

// Helper type for standard API response
export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
}
