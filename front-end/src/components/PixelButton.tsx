import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "@/lib/utils"

const pixelButtonVariants = cva(
  "inline-flex items-center justify-center whitespace-nowrap font-pixel font-bold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border-2 border-solid",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground border-primary hover:shadow-pixel transform hover:-translate-y-0.5 active:translate-y-0",
        destructive: "bg-destructive text-destructive-foreground border-destructive hover:shadow-pixel transform hover:-translate-y-0.5",
        outline: "border-primary text-primary bg-background hover:bg-primary hover:text-primary-foreground hover:shadow-pixel transform hover:-translate-y-0.5",
        secondary: "bg-secondary text-secondary-foreground border-secondary hover:shadow-pixel transform hover:-translate-y-0.5",
        ghost: "border-transparent hover:bg-accent hover:text-accent-foreground hover:border-accent",
        link: "text-primary underline-offset-4 hover:underline border-transparent",
        hero: "bg-gradient-primary text-primary-foreground border-primary-glow hover:shadow-glow animate-pixel-glow transform hover:-translate-y-1 hover:scale-105",
        neon: "bg-background text-primary border-primary shadow-glow hover:bg-primary hover:text-primary-foreground transform hover:-translate-y-0.5",
      },
      size: {
        default: "h-12 px-6 py-3",
        sm: "h-10 px-4 py-2 text-sm",
        lg: "h-14 px-8 py-4 text-lg",
        icon: "h-12 w-12",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

export interface PixelButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof pixelButtonVariants> {
  asChild?: boolean
}

const PixelButton = React.forwardRef<HTMLButtonElement, PixelButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : "button"
    return (
      <Comp
        className={cn(pixelButtonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    )
  }
)
PixelButton.displayName = "PixelButton"

export { PixelButton, pixelButtonVariants }