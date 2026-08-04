import {
  authCaptcha,
  authActiveGroup,
  authLogin,
  authLogout,
  authMe,
  authResetDefaultGroup,
  authForgotPassword,
  authResetPassword,
  authPassword,
  usersSendPasswordLink,
  type AuthActiveGroup200,
  type AuthCaptcha200,
  type AuthLogin200,
  type AuthMe200,
  type AuthResetDefaultGroup200,
  type AuthForgotPassword200,
  type AuthResetPassword200,
  type AuthPassword200,
  type UsersSendPasswordLink200,
  type ForgotPasswordRequest,
  type ResetPasswordRequest,
  type ChangePasswordRequest,
  type LoginRequest,
  type SetActiveGroupRequest,
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

  public static setActiveGroup(data: SetActiveGroupRequest): Promise<AuthActiveGroup200> {
    return authActiveGroup(data);
  }

  public static resetDefaultGroup(): Promise<AuthResetDefaultGroup200> {
    return authResetDefaultGroup();
  }

  public static logout(): Promise<void> {
    return authLogout();
  }

  public static captcha(): Promise<AuthCaptcha200> {
    return authCaptcha();
  }

  public static forgotPassword(data: ForgotPasswordRequest): Promise<AuthForgotPassword200> {
    return authForgotPassword(data);
  }

  public static resetPassword(data: ResetPasswordRequest): Promise<AuthResetPassword200> {
    return authResetPassword(data);
  }

  public static changePassword(data: ChangePasswordRequest): Promise<AuthPassword200> {
    return authPassword(data);
  }

  public static sendPasswordLink(user: number | string): Promise<UsersSendPasswordLink200> {
    return usersSendPasswordLink(typeof user === 'string' ? Number(user) : user);
  }

  public static async playCaptchaAudio(key: string): Promise<HTMLAudioElement> {
    const response = await axiosInstance.get<Blob>('/auth/captcha/audio', {
      params: { key },
      responseType: 'blob',
    });
    const audioUrl = URL.createObjectURL(response.data);
    const audio = new Audio(audioUrl);
    audio.onended = () => URL.revokeObjectURL(audioUrl);
    audio.onerror = () => URL.revokeObjectURL(audioUrl);
    await audio.play();
    return audio;
  }
}
