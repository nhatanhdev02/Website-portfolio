import * as React from "react"
import { cn } from "@/lib/utils"

const PixelCard = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement> & { variant?: 'default' | 'glow' | 'hover' }
>(({ className, variant = 'default', ...props }, ref) => (
  <div
    ref={ref}
    className={cn(
      "bg-card text-card-foreground border-2 border-solid border-border font-pixel",
      variant === 'glow' && "shadow-glow hover:shadow-pixel transition-shadow",
      variant === 'hover' && "hover:shadow-pixel hover:border-primary transition-all hover:-translate-y-1 cursor-pointer",
      className
    )}
    {...props}
  />
))
PixelCard.displayName = "PixelCard"

const PixelCardHeader = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div ref={ref} className={cn("flex flex-col space-y-1.5 p-6", className)} {...props} />
))
PixelCardHeader.displayName = "PixelCardHeader"

const PixelCardTitle = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLHeadingElement>
>(({ className, ...props }, ref) => (
  <h3
    ref={ref}
    className={cn("font-display font-bold text-2xl leading-none tracking-tight text-primary", className)}
    {...props}
  />
))
PixelCardTitle.displayName = "PixelCardTitle"

const PixelCardDescription = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => (
  <p ref={ref} className={cn("text-sm text-muted-foreground", className)} {...props} />
))
PixelCardDescription.displayName = "PixelCardDescription"

const PixelCardContent = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div ref={ref} className={cn("p-6 pt-0", className)} {...props} />
))
PixelCardContent.displayName = "PixelCardContent"

const PixelCardFooter = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div ref={ref} className={cn("flex items-center p-6 pt-0", className)} {...props} />
))
PixelCardFooter.displayName = "PixelCardFooter"

export { PixelCard, PixelCardHeader, PixelCardFooter, PixelCardTitle, PixelCardDescription, PixelCardContent }