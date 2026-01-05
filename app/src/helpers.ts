export interface ProgressTracking {
  totalBytes: number;
  bytesUploaded: number;
  progressPercent: number;
}

export const progressTrackingUpload = (
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
        resolve(
          new Response(xhr.response, {
            status: xhr.status,
            statusText: xhr.statusText,
          }),
        );
      }
    };

    xhr.open(init.method, url, true);
    xhr.send(init.body);
  });
