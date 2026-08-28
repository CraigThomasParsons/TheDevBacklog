import { favicons } from 'favicons';
import { promises as fs } from 'node:fs';
import path from 'node:path';

const projectRoot = process.cwd();
const source = path.join(projectRoot, 'resources', 'favicon-source.svg');
const outputDir = path.join(projectRoot, 'public', 'favicons');

await fs.mkdir(outputDir, { recursive: true });

const configuration = {
  path: '/favicons/',
  appName: 'TheDevBacklog',
  appShortName: 'Dev',
  appDescription: 'TheDevBacklog',
  developerName: 'Elasticgun',
  developerURL: 'https://elasticgun.com',
  background: '#ffffff',
  theme_color: '#16a34a',
  icons: {
    android: true,
    appleIcon: true,
    appleStartup: false,
    favicons: true,
    windows: true,
    yandex: false
  }
};

const response = await favicons(source, configuration);

await Promise.all(
  [...response.images, ...response.files].map((asset) =>
    fs.writeFile(path.join(outputDir, asset.name), asset.contents)
  )
);

console.log('Favicons generated in', outputDir);
