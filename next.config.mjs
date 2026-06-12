/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',   // required for Docker deployment
  typescript: {
    ignoreBuildErrors: true,
  }
}

export default nextConfig
