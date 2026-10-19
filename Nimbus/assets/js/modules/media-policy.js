// 媒体挂件的移动端加载策略，共用同一断点与触屏判断。
export const isMobileDevice = () => matchMedia('(max-width: 768px), (pointer: coarse)').matches;

export function isAllowedOnDevice(setting, mobile = isMobileDevice()) {
  return Boolean(setting) && (!mobile || setting.mobile === true);
}
