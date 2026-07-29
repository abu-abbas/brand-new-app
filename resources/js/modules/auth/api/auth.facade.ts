import {
  authCaptcha,
  authLogin,
  authLogout,
  authMe,
  type AuthCaptcha200,
  type AuthLogin200,
  type AuthMe200,
  type LoginRequest,
} from '@/api/generated/api';
import { axiosInstance } from '@/lib/axios';

export class AuthFacade {
  public static async login(data: LoginRequest): Promise<AuthLogin200> {
    await axiosInstance.get('/sanctum/csrf-cookie', { baseURL: '/' });

    return authLogin(data);
  }

  public static me(): Promise<AuthMe200> {
    return authMe();
  }

  public static logout(): Promise<void> {
    return authLogout();
  }

  public static captcha(): Promise<AuthCaptcha200> {
    return authCaptcha();
  }
}
