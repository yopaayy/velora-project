import { create } from "zustand";
import { persist } from "zustand/middleware";

interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

interface Business {
  id: string;
  name: string;
}

interface Branch {
  id: string;
  name: string;
}

interface AuthState {
  user: User | null;
  business: Business | null;
  branch: Branch | null;
  isAuthenticated: boolean;
  setAuth: (user: User, token: string, business: Business, branch: Branch) => void;
  clearAuth: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      business: null,
      branch: null,
      isAuthenticated: false,
      setAuth: (user, token, business, branch) => {
        localStorage.setItem("auth_token", token);
        localStorage.setItem("business_id", business.id);
        localStorage.setItem("branch_id", branch.id);
        set({ user, business, branch, isAuthenticated: true });
      },
      clearAuth: () => {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("business_id");
        localStorage.removeItem("branch_id");
        set({ user: null, business: null, branch: null, isAuthenticated: false });
      },
    }),
    {
      name: "auth-storage", // keys for localStorage
      partialize: (state) => ({ user: state.user, business: state.business, branch: state.branch, isAuthenticated: state.isAuthenticated }),
    }
  )
);
