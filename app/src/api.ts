export const BASE_URL = process.env.NODE_ENV === 'development' ? 'https://clipboard.achri.dk/' : '';

export interface ClipboardItemInfo {
  label: string;
  mime: string;
  size: number;
  hash: string;
  time: number;
}
export const fetchClipboardInfo = (): Promise<ClipboardItemInfo[]> =>
  fetch(`${BASE_URL}?info`).then((response) => response.json());

export const fetchClipboardBlob = (label: string): Promise<Blob> =>
  fetch(`${BASE_URL}?clip=${label}`).then((response) => response.blob());

interface ProgressTracking {
  totalBytes: number;
  bytesUploaded: number;
  progressPercent: number;
}

export const fileFromBlob = (blob: Blob, type: string) =>
  new File([blob], `LABEL_${encodeURIComponent(type)}`, { type });

export const uploadToClipboard = (
  formData: FormData,
  onProgress: (progress: ProgressTracking) => void,
): Promise<Response> =>
  progressTrackingUpload(`${BASE_URL}?`, { method: 'POST', body: formData }, onProgress);

const progressTrackingUpload = (
  url: string,
  init: {
    method: string;
    body: XMLHttpRequestBodyInit;
  },
  onProgress: (progress: ProgressTracking) => void,
): Promise<Response> =>
  new Promise((resolve, reject) => {
    const handler = ({ loaded, total }: ProgressEvent) => {
      onProgress({
        totalBytes: total,
        bytesUploaded: loaded,
        progressPercent: (loaded / total) * 100,
      });
    };

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('loadstart', handler);
    xhr.upload.addEventListener('load', handler);
    xhr.upload.addEventListener('loadend', handler);
    xhr.upload.addEventListener('progress', handler);

    xhr.addEventListener('error', (error) => reject(error));

    xhr.onreadystatechange = () => {
      if (xhr.readyState === 4) {
        try {
          const response = new Response(xhr.response, {
            status: xhr.status,
            statusText: xhr.statusText,
          });
          resolve(response);
        } catch (error) {
          reject(error);
        }
      }
    };

    xhr.open(init.method, url, true);
    try {
      xhr.send(init.body);
    } catch (error) {
      reject(error);
    }
  });
